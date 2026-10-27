<script setup lang="ts">
import { CircleCheck, Droplets, GripHorizontal, Minus, RefreshCw, Thermometer, TriangleAlert } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

export interface IotTelemetryReading {
    device_id: string;
    temperature: number;
    humidity: number;
    measured_at: string | null;
    received_at: string | null;
    status: 'safe' | 'danger';
    message: string;
    safe_temperature_min: number;
    safe_temperature_max: number;
}

const props = defineProps<{
    initialReading: IotTelemetryReading;
    endpoint: string;
}>();

const STORAGE_KEY = 'levelup-iot-widget-position';
const reading = ref(props.initialReading);
const widget = ref<HTMLElement | null>(null);
const position = ref({ x: 16, y: 80 });
const positioned = ref(false);
const collapsed = ref(false);
const isRefreshing = ref(false);
let pollTimer: ReturnType<typeof setInterval> | null = null;
let dragging: { pointerX: number; pointerY: number; widgetX: number; widgetY: number } | null = null;

const isDangerous = computed(() => reading.value.status === 'danger');
const statusTitle = computed(() => (isDangerous.value ? 'Température dangereuse' : 'Situation normale'));
const readingTime = computed(() => {
    const value = reading.value.measured_at ?? reading.value.received_at;

    if (!value) return 'Date inconnue';

    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
});

const widgetStyle = computed(() =>
    positioned.value ? { left: `${position.value.x}px`, top: `${position.value.y}px` } : { right: '1rem', top: '5rem' },
);

const clampPosition = (candidate = position.value) => {
    const width = widget.value?.offsetWidth ?? 336;
    const height = widget.value?.offsetHeight ?? 180;
    const margin = 8;

    position.value = {
        x: Math.min(Math.max(margin, candidate.x), Math.max(margin, window.innerWidth - width - margin)),
        y: Math.min(Math.max(margin, candidate.y), Math.max(margin, window.innerHeight - height - margin)),
    };
};

const savePosition = () => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(position.value));
    } catch {
        // Le stockage local peut être désactivé sans empêcher le déplacement.
    }
};

const stopDragging = () => {
    if (!dragging) return;

    dragging = null;
    document.body.style.removeProperty('user-select');
    savePosition();
};

const moveWidget = (event: PointerEvent) => {
    if (!dragging) return;

    clampPosition({
        x: dragging.widgetX + event.clientX - dragging.pointerX,
        y: dragging.widgetY + event.clientY - dragging.pointerY,
    });
};

const startDragging = (event: PointerEvent) => {
    if (event.button !== 0) return;

    dragging = {
        pointerX: event.clientX,
        pointerY: event.clientY,
        widgetX: position.value.x,
        widgetY: position.value.y,
    };
    document.body.style.userSelect = 'none';
    (event.currentTarget as HTMLElement).setPointerCapture?.(event.pointerId);
};

const moveWithKeyboard = (event: KeyboardEvent) => {
    const movements: Record<string, [number, number]> = {
        ArrowLeft: [-10, 0],
        ArrowRight: [10, 0],
        ArrowUp: [0, -10],
        ArrowDown: [0, 10],
    };
    const movement = movements[event.key];

    if (!movement) return;

    event.preventDefault();
    clampPosition({ x: position.value.x + movement[0], y: position.value.y + movement[1] });
    savePosition();
};

const refresh = async () => {
    if (isRefreshing.value || document.hidden) return;

    isRefreshing.value = true;

    try {
        const response = await fetch(props.endpoint, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) return;

        const payload = (await response.json()) as { data: IotTelemetryReading | null };
        if (payload.data) reading.value = payload.data;
    } catch {
        // La dernière valeur reste affichée si le réseau est momentanément indisponible.
    } finally {
        isRefreshing.value = false;
    }
};

const toggleCollapsed = async () => {
    collapsed.value = !collapsed.value;
    await nextTick();
    clampPosition();
    savePosition();
};

const handleVisibilityChange = () => {
    if (!document.hidden) void refresh();
};

const handleResize = () => clampPosition();

onMounted(async () => {
    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null') as { x?: unknown; y?: unknown } | null;
        if (stored && typeof stored.x === 'number' && typeof stored.y === 'number') {
            position.value = { x: stored.x, y: stored.y };
        } else {
            position.value = { x: window.innerWidth - (widget.value?.offsetWidth ?? 336) - 16, y: 80 };
        }
    } catch {
        position.value = { x: window.innerWidth - (widget.value?.offsetWidth ?? 336) - 16, y: 80 };
    }

    positioned.value = true;
    await nextTick();
    clampPosition();

    window.addEventListener('pointermove', moveWidget);
    window.addEventListener('pointerup', stopDragging);
    window.addEventListener('pointercancel', stopDragging);
    window.addEventListener('resize', handleResize);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    pollTimer = setInterval(() => void refresh(), 15_000);
});

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer);
    window.removeEventListener('pointermove', moveWidget);
    window.removeEventListener('pointerup', stopDragging);
    window.removeEventListener('pointercancel', stopDragging);
    window.removeEventListener('resize', handleResize);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.body.style.removeProperty('user-select');
});
</script>

<template>
    <aside
        ref="widget"
        :style="widgetStyle"
        class="iot-widget fixed z-50 w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-2xl border shadow-2xl backdrop-blur-xl"
        :class="
            isDangerous
                ? 'border-red-400/70 bg-red-950/95 text-white shadow-red-950/30'
                : 'border-emerald-300/70 bg-white/95 text-slate-900 shadow-emerald-950/20 dark:bg-slate-950/95 dark:text-white'
        "
        :role="isDangerous ? 'alert' : 'status'"
        aria-live="polite"
        aria-label="Informations du capteur IoT"
    >
        <div
            class="flex touch-none items-center gap-2 px-2 py-2 select-none"
            :class="isDangerous ? 'bg-red-900/70' : 'bg-emerald-50 dark:bg-emerald-950/60'"
        >
            <button
                type="button"
                class="flex min-w-0 flex-1 cursor-move items-center gap-3 rounded-xl px-2 py-1 text-left focus-visible:ring-2 focus-visible:ring-current focus-visible:outline-none"
                aria-label="Déplacer l’encart du capteur avec la souris ou les touches fléchées"
                @pointerdown="startDragging"
                @keydown="moveWithKeyboard"
            >
                <GripHorizontal class="size-5 shrink-0 opacity-60" aria-hidden="true" />
                <span class="min-w-0 flex-1">
                    <span class="block text-[0.65rem] font-black tracking-[0.16em] uppercase opacity-65">Capteur en direct</span>
                    <span class="block truncate text-xs font-semibold opacity-80">{{ reading.device_id }}</span>
                </span>
                <span class="relative flex size-2.5" aria-hidden="true">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-current opacity-50"></span>
                    <span class="relative inline-flex size-2.5 rounded-full bg-current"></span>
                </span>
            </button>
            <button
                type="button"
                class="rounded-lg p-1.5 transition hover:bg-black/10 focus-visible:ring-2 focus-visible:ring-current focus-visible:outline-none"
                :aria-label="collapsed ? 'Déployer les informations du capteur' : 'Réduire les informations du capteur'"
                :aria-expanded="!collapsed"
                @click="toggleCollapsed"
            >
                <Minus class="size-4" :class="collapsed ? 'rotate-90' : ''" aria-hidden="true" />
            </button>
        </div>

        <div v-if="!collapsed" class="p-4">
            <div class="flex items-start gap-3">
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl"
                    :class="isDangerous ? 'bg-red-500 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200'"
                >
                    <TriangleAlert v-if="isDangerous" class="size-6" aria-hidden="true" />
                    <CircleCheck v-else class="size-6" aria-hidden="true" />
                </span>
                <div>
                    <p class="font-black" :class="isDangerous ? 'text-white' : 'text-emerald-800 dark:text-emerald-200'">
                        {{ statusTitle }}
                    </p>
                    <p class="mt-0.5 text-xs leading-relaxed opacity-75">{{ reading.message }}</p>
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-2">
                <div class="rounded-xl bg-black/5 p-3 dark:bg-white/10">
                    <dt class="flex items-center gap-1.5 text-xs font-semibold opacity-65">
                        <Thermometer class="size-4" aria-hidden="true" /> Température
                    </dt>
                    <dd class="mt-1 text-2xl font-black">{{ Number(reading.temperature).toLocaleString('fr-FR') }} °C</dd>
                </div>
                <div class="rounded-xl bg-black/5 p-3 dark:bg-white/10">
                    <dt class="flex items-center gap-1.5 text-xs font-semibold opacity-65">
                        <Droplets class="size-4" aria-hidden="true" /> Humidité
                    </dt>
                    <dd class="mt-1 text-2xl font-black">{{ Number(reading.humidity).toLocaleString('fr-FR') }} %</dd>
                </div>
            </dl>

            <div class="mt-3 flex items-center justify-between gap-3 text-[0.68rem] opacity-60">
                <span>Relevé le {{ readingTime }}</span>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md p-1 font-bold hover:bg-black/10 focus-visible:ring-2 focus-visible:ring-current focus-visible:outline-none"
                    :disabled="isRefreshing"
                    @click="refresh"
                >
                    <RefreshCw class="size-3" :class="isRefreshing ? 'animate-spin' : ''" aria-hidden="true" />
                    Actualiser
                </button>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.iot-widget {
    transition:
        width 160ms ease,
        box-shadow 160ms ease;
}

@media (prefers-reduced-motion: reduce) {
    .iot-widget,
    .iot-widget * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
