public function show(<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Rework;
use App\Models\Champion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $comments = Comment::with(['user', 'rework.champion'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $comments,
                'message' => 'Comments retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching comments: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validated = $request->validate([
                'rework_id' => 'required|integer|exists:reworks,id',
                'content' => 'required|string|min:10|max:1000'
            ]);

            // Create the comment
            $comment = Comment::create([
                'rework_id' => $validated['rework_id'],
                'user_id' => $user->id,
                'content' => $validated['content']
            ]);

            // Track comment for points (+1 point) - now properly handling return value
            $pointsResult = $user->trackComment();
            
            // Load comment with relationships
            $comment->load(['user', 'rework.champion']);

            return response()->json([
                'success' => true,
                'message' => 'Comment created successfully! ' . $pointsResult['message'],
                'data' => [
                    'comment' => $comment,
                    'points_earned' => $pointsResult['points_earned'],
                    'total_points' => $pointsResult['total_points']
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating comment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $comment = Comment::with(['user', 'rework.champion'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Comment retrieved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching comment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $comment = Comment::findOrFail($id);

            // Check if user owns the comment or is admin
            if ($comment->user_id !== $user->id && !$user->roles->contains('name', 'admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to edit this comment'
                ], 403);
            }

            $request->validate([
                'content' => 'required|string|min:10|max:1000'
            ]);

            $comment->update([
                'content' => $request->content
            ]);

            $comment->load(['user', 'rework.champion']);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => $comment
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating comment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $comment = Comment::findOrFail($id);

            // Check if user owns the comment or is admin
            if ($comment->user_id !== $user->id && !$user->roles->contains('name', 'admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this comment'
                ], 403);
            }

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting comment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Champion-specific comment methods
    public function getChampionReworkComments(Request $request, Champion $champion): JsonResponse
    {
        try {
            if (!$champion->rework) {
                return response()->json([
                    'success' => false,
                    'message' => 'This champion does not have a rework'
                ], 404);
            }

            $comments = Comment::where('rework_id', $champion->rework->id)
                ->with(['user'])
                ->latest()
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $comments,
                'message' => 'Champion rework comments retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching champion rework comments: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addCommentToChampionRework(Request $request, Champion $champion): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if (!$champion->rework) {
                return response()->json([
                    'success' => false,
                    'message' => 'This champion does not have a rework to comment on'
                ], 404);
            }

            $request->validate([
                'content' => 'required|string|min:10|max:1000'
            ]);

            // Create the comment
            $comment = Comment::create([
                'rework_id' => $champion->rework->id,
                'user_id' => $user->id,
                'content' => $request->content
            ]);

            // Track comment for points
            $pointsResult = $user->trackComment();
            
            // Load comment with relationships
            $comment->load(['user']);

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully! ' . $pointsResult['message'],
                'data' => [
                    'comment' => $comment,
                    'points_earned' => $pointsResult['points_earned'],
                    'total_points' => $user->getTotalPoints()
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error adding comment to champion rework: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}