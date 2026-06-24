<!DOCTYPE html>
<html>

<head>
    <title>사내 게시판</title>
</head>

<body>

    <h1>📋 사내 게시판</h1>

    <hr>

    <h2>게시판 목록</h2>

    @foreach ($posts as $post)
        <p>
            <a href="/board/{{ $post->id }}">
                {{ $post['id']}} - {{ $post->title }} - {{ $post -> content}}
            </a>
        </p>
    @endforeach
    {{ $posts->links() }}
    <button onclick="location.href = '/board/create'">등록하기</button>
</body>

</html>
