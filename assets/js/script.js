function save_as_draft(form) {
    document.getElementById('is_draft').value = 1;
    return document.getElementById(form).submit();
}