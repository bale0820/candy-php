<!DOCTYPE html>
<html>
<body>

<h1>게시글 수정</h1>

<form action="/board/{{ $post->id }}/update" method="POST">
    @csrf

    제목 :
    <input
        type="text"
        name="title"
        value="{{ $post->title }}"
    >

    <br><br>

    내용 :
    <textarea name="content">{{ $post->content }}</textarea>

    <br><br>

    <button type="submit">수정하기</button>

</form>

</body>
</html>
