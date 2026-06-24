<!DOCTYPE html>
<html>

<head>
    <title>{{ $post['title'] }}</title>
</head>

<body>

    <h1>{{ $post['title'] }}</h1>

    <hr>

    <p>번호 : {{ $post['id'] }}</p>

    <p>{{ $post->content }}</p>
    <a href="/board/{{ $post['id'] }}/edit">수정하기</a>
    <form action="/board/{{ $post->id }}/delete" method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?')">
        @csrf
        <button type="submit">삭제하기</button>
    </form>
    <a href="/">목록으로</a>

</body>

</html>
