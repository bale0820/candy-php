<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function index()
    {
        $posts = Post::latest()->paginate(10);

        return view('board.index', compact('posts'));
    }

    public function show($id)
    {
        $post = Post::find($id);

        // $post = null;

        // foreach ($posts as $item) {
        //     if ($item['id'] == $id) {
        //         $post = $item;
        //         break;
        //     }
        // }

        return view('board.show', [
            'post' => $post
        ]);
    }

    public function create()
    {
        return view('board.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:100',
            'content' => 'required',
        ]);

        Post::create([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return redirect('/board/create')
            ->with('success', '등록이 완료되었습니다.');
    }


    public function edit($id)
    {
        $post = Post::findOrFail($id);

        return view('board.edit', compact('post'));
    }


    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $post->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return redirect('/board');
    }

    public function destroy($id)
    {
        dump($id);
        Post::destroy($id);

        return redirect('/board');
    }
}
