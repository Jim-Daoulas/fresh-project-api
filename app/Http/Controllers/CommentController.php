<?php

namespace App\Http\Controllers;

use App\Models\Champion;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    /**
     * Get comments for a champion's rework
     */
    public function getChampionReworkComments(Champion $champion): JsonResponse
    {
        try {
            // Load the rework with its comments and users
            $champion->load(['rework.comments.user']);
            
            if (!$champion->rework) {
                return response()->json([
                    'success' => false,
                    'message' => 'This champion does not have a rework'
                ], 404);
            }

            $comments = $champion->rework->comments;

            return response()->json([
                'success' => true,
                'data' => $comments,
                'message' => 'Comments retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a comment to a champion's rework
     */
    public function addCommentToChampionRework(Request $request, Champion $champion): JsonResponse
    {
        try {
            // Load the rework
            $champion->load(['rework']);
            
            if (!$champion->rework) {
                return response()->json([
                    'success' => false,
                    'message' => 'This champion does not have a rework to comment on'
                ], 404);
            }

            // Validate the request
            $validated = $request->validate([
                'content' => 'required|string|min:3|max:500'
            ]);

            // Create the comment
            $comment = Comment::create([
                'rework_id' => $champion->rework->id,
                'user_id' => $request->user()->id,
                'content' => $validated['content']
            ]);

            // Load the user relationship
            $comment->load('user');

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Comment added successfully'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}