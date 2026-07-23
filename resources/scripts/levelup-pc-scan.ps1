$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$scanId = '__SCAN_UUID__'
$scanToken = '__SCAN_TOKEN__'
$uploadUrl = '__UPLOAD_URL__'
$resultUrl = '__RESULT_URL__'

function Get-SafeCimInstance {
    param([Parameter(Mandatory = $true)][string]$ClassName)

    try {
        return @(Get-CimInstance -ClassName $ClassName -ErrorAction Stop)
    }
    catch {
        return @()
    }
}

Write-Host 'LevelUp Pulse - Analyse de compatibilite PC' -ForegroundColor Cyan
Write-Host 'Lecture des composants (aucun droit administrateur requis)...'

try {
    [Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12

    $osEntry = @(Get-SafeCimInstance -ClassName 'Win32_OperatingSystem') | Select-Object -First 1
    $directXEntry = Get-ItemProperty -Path 'HKLM:\SOFTWARE\Microsoft\DirectX' -Name 'Version' -ErrorAction SilentlyContinue

    $cpuEntries = @(
        Get-SafeCimInstance -ClassName 'Win32_Processor' | ForEach-Object {
            [ordered]@{
                name = [string]$_.Name
                cores = if ($null -ne $_.NumberOfCores) { [int]$_.NumberOfCores } else { $null }
                logical_processors = if ($null -ne $_.NumberOfLogicalProcessors) { [int]$_.NumberOfLogicalProcessors } else { $null }
                max_clock_mhz = if ($null -ne $_.MaxClockSpeed) { [int]$_.MaxClockSpeed } else { $null }
            }
        }
    )
    if ($cpuEntries.Count -eq 0) {
        $registryCpu = Get-ItemProperty -Path 'HKLM:\HARDWARE\DESCRIPTION\System\CentralProcessor\0' -ErrorAction SilentlyContinue
        if ($null -ne $registryCpu.ProcessorNameString) {
            $cpuEntries = @(
                [ordered]@{
                    name = [string]$registryCpu.ProcessorNameString
                    cores = $null
                    logical_processors = $null
                    max_clock_mhz = if ($null -ne $registryCpu.'~MHz') { [int]$registryCpu.'~MHz' } else { $null }
                }
            )
        }
    }

    $gpuEntries = @(
        Get-SafeCimInstance -ClassName 'Win32_VideoController' | ForEach-Object {
            $vram = $null
            if ($null -ne $_.AdapterRAM -and [uint64]$_.AdapterRAM -gt 0) {
                $vram = [uint64]$_.AdapterRAM
            }

            [ordered]@{
                name = [string]$_.Name
                vram_bytes = $vram
                vram_is_estimate = $true
                driver_version = if ($null -ne $_.DriverVersion) { [string]$_.DriverVersion } else { $null }
            }
        }
    )

    $memoryModules = @(Get-SafeCimInstance -ClassName 'Win32_PhysicalMemory')
    [uint64]$totalMemory = 0
    foreach ($module in $memoryModules) {
        if ($null -ne $module.Capacity) {
            $totalMemory += [uint64]$module.Capacity
        }
    }
    if ($totalMemory -eq 0 -and $null -ne $osEntry.TotalVisibleMemorySize) {
        $totalMemory = [uint64]$osEntry.TotalVisibleMemorySize * 1024
    }

    $volumes = @()
    try {
        $volumes = @(
            Get-Volume -ErrorAction Stop |
                Where-Object { $_.DriveType -eq 'Fixed' -and $null -ne $_.DriveLetter } |
                ForEach-Object {
                    [ordered]@{
                        drive = "$($_.DriveLetter):"
                        filesystem = if ($null -ne $_.FileSystemType) { [string]$_.FileSystemType } else { $null }
                        total_bytes = if ($null -ne $_.Size) { [uint64]$_.Size } else { $null }
                        free_bytes = if ($null -ne $_.SizeRemaining) { [uint64]$_.SizeRemaining } else { $null }
                    }
                }
        )
    }
    catch {
        $volumes = @(
            Get-SafeCimInstance -ClassName 'Win32_LogicalDisk' |
                Where-Object { $_.DriveType -eq 3 } |
                ForEach-Object {
                    [ordered]@{
                        drive = [string]$_.DeviceID
                        filesystem = if ($null -ne $_.FileSystem) { [string]$_.FileSystem } else { $null }
                        total_bytes = if ($null -ne $_.Size) { [uint64]$_.Size } else { $null }
                        free_bytes = if ($null -ne $_.FreeSpace) { [uint64]$_.FreeSpace } else { $null }
                    }
                }
        )
    }
    if ($volumes.Count -eq 0) {
        $volumes = @(
            [System.IO.DriveInfo]::GetDrives() |
                Where-Object { $_.DriveType -eq [System.IO.DriveType]::Fixed -and $_.IsReady } |
                ForEach-Object {
                    [ordered]@{
                        drive = $_.Name.Substring(0, 2)
                        filesystem = if ($null -ne $_.DriveFormat) { [string]$_.DriveFormat } else { $null }
                        total_bytes = [uint64]$_.TotalSize
                        free_bytes = [uint64]$_.AvailableFreeSpace
                    }
                }
        )
    }

    $physicalDisks = @()
    try {
        $physicalDisks = @(
            Get-PhysicalDisk -ErrorAction Stop | ForEach-Object {
                [ordered]@{
                    model = if ($null -ne $_.FriendlyName) { [string]$_.FriendlyName } else { $null }
                    media_type = if ($null -ne $_.MediaType) { [string]$_.MediaType } else { 'Unspecified' }
                    total_bytes = if ($null -ne $_.Size) { [uint64]$_.Size } else { $null }
                }
            }
        )
    }
    catch {
        $physicalDisks = @(
            Get-SafeCimInstance -ClassName 'Win32_DiskDrive' | ForEach-Object {
                [ordered]@{
                    model = if ($null -ne $_.Model) { [string]$_.Model } else { $null }
                    media_type = if ($null -ne $_.MediaType) { [string]$_.MediaType } else { 'Unspecified' }
                    total_bytes = if ($null -ne $_.Size) { [uint64]$_.Size } else { $null }
                }
            }
        )
    }

    $payload = [ordered]@{
        schema_version = 1
        collected_at = (Get-Date).ToUniversalTime().ToString('o')
        os = [ordered]@{
            caption = if ($null -ne $osEntry.Caption) { [string]$osEntry.Caption } else { 'Microsoft Windows' }
            version = if ($null -ne $osEntry.Version) { [string]$osEntry.Version } else { [Environment]::OSVersion.Version.ToString() }
            architecture = if ($null -ne $osEntry.OSArchitecture) { [string]$osEntry.OSArchitecture } elseif ([Environment]::Is64BitOperatingSystem) { '64-bit' } else { '32-bit' }
            directx_version = if ($null -ne $directXEntry.Version) { [string]$directXEntry.Version } else { $null }
        }
        cpu = $cpuEntries
        gpu = $gpuEntries
        memory = [ordered]@{
            total_bytes = $totalMemory
        }
        storage = [ordered]@{
            volumes = $volumes
            physical_disks = $physicalDisks
        }
    }

    $json = $payload | ConvertTo-Json -Depth 8 -Compress
    $utf8 = New-Object System.Text.UTF8Encoding($false)
    $body = $utf8.GetBytes($json)
    $headers = @{
        'Accept' = 'application/json'
        'X-LevelUp-Scan-Token' = $scanToken
    }

    Write-Host 'Envoi securise vers LevelUp Pulse...'
    Invoke-RestMethod -Uri $uploadUrl -Method Post -Headers $headers -ContentType 'application/json; charset=utf-8' -Body $body | Out-Null

    Write-Host 'Analyse recue. Le resultat va apparaitre dans votre navigateur.' -ForegroundColor Green
    Start-Process $resultUrl
}
catch {
    Write-Host 'Le test n a pas pu etre envoye. Verifiez votre connexion et relancez un nouveau test depuis la fiche du jeu.' -ForegroundColor Red
    exit 1
}
