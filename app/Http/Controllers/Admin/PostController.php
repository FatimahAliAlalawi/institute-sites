<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\Uploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function index()
    {
        return view('admin.posts.index', ['posts' => Post::orderByDesc('published_at')->orderByDesc('id')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.posts.form', ['post' => new Post(['is_published' => true, 'published_at' => now()])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image'] = $request->hasFile('image') ? Uploader::store($request->file('image'), 'posts') : null;
        Post::create($data);

        return redirect()->route('admin.posts.index')->with('status', 'تم نشر الخبر.');
    }

    public function edit(Post $post)
    {
        return view('admin.posts.form', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request, $post);
        $data['image'] = Uploader::replace($request->file('image'), $post->image, 'posts', $request->boolean('remove_image'));
        $post->update($data);

        return redirect()->route('admin.posts.edit', $post)->with('status', 'تم حفظ التعديلات.');
    }

    public function destroy(Post $post)
    {
        Uploader::delete($post->image);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', 'تم حذف الخبر.');
    }

    protected function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:190',
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('posts', 'slug')->ignore($post?->id)],
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'published_at' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        unset($data['image']);
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['published_at'] ?? now();

        return $data;
    }
}
