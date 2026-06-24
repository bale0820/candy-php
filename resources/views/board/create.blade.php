<!DOCTYPE html>
<html>
<body>

@if(session('success'))
<script>
    alert('{{ session('success') }}');
    location.href = '/';
</script>
@endif

<h1>게시글 작성</h1>

<form action="/store" method="POST" onsubmit="return confirm('등록하시겠습니까?')">
    @csrf

    제목 :
    <input type="text" name="title">

    <br><br>

    내용 :
    <textarea name="content"></textarea>

    <br><br>

    <button type="submit">등록</button>
</form>

</body>
</html>
