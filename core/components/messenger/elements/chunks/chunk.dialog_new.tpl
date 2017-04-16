<div class="dialog-header">
	<div class="dialog-name">Создание диалога</div>
</div>
<form id="messenger_dialog_create">
	<div class="form-group">
		<label>Получатель(и)</label>
		<select name="users" id="users" multiple="multiple"></select>
	</div>
	<div class="form-group create-dialog-name">
		<label>Название диалога</label>
		<input type="text" name="name">
	</div>
	<div class="form-group">
		<label>Сообщение</label>
		<textarea name="message"></textarea>
	</div>
	<button type="submit">Отправить</button>
</form>