<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\ArticleAiAssistant;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ArticleAiController extends Controller
{
    public function trending(
        Request $request,
        ArticleAiAssistant $assistant,
        AuditLogger $auditLogger,
    ): JsonResponse {
        try {
            $suggestions = $assistant->suggestTrendingGames((int) $request->user()->id);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'La veille IA est temporairement indisponible. Réessayez dans quelques instants.',
            ], 503);
        }

        $auditLogger->log($request, 'article.ai.trends_requested', metadata: [
            'suggestions_count' => count($suggestions['games']),
            'sources_count' => count($suggestions['sources']),
        ]);

        return response()->json($suggestions);
    }

    public function correct(
        Request $request,
        Game $game,
        ArticleAiAssistant $assistant,
        AuditLogger $auditLogger,
    ): JsonResponse {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'content' => ['required', 'string', 'min:20', 'max:30000'],
        ]);

        try {
            $correction = $assistant->correctArticle(
                game: $game,
                title: $validated['title'] ?? null,
                content: $validated['content'],
                userId: (int) $request->user()->id,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'La correction IA est temporairement indisponible. Votre brouillon reste inchangé.',
            ], 503);
        }

        $auditLogger->log($request, 'article.ai.correction_requested', $game, [
            'content_characters' => mb_strlen($validated['content']),
            'changes_count' => count($correction['changes']),
            'editorial_notes_count' => count($correction['editorial_notes']),
        ]);

        return response()->json($correction);
    }
}
