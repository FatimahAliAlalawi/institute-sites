<form method="post" action="{{ $action }}" data-confirm="{{ $confirm ?? 'هل أنت متأكد من الحذف؟ لا يمكن التراجع.' }}">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i> {{ $label ?? 'حذف' }}</button>
</form>
