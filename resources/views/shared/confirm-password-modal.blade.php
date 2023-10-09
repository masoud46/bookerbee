<div id="confirm-password-modal" class="modal fade" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content shadow">
			<form>
				<div class="modal-header shadow-sm text-bg-warning">
					<h6 class="modal-title" id="confirm-password-modal-title">{{ __('Confirmation') }}</h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body px-4" tabindex="-1">
					<p>{{ __('Please confirm your password before continuing.') }}</p>
					<label class="form-label">{{ __('Password') }}</label>
					<input name="password" type="password" class="form-control">
				</div>
				<div class="modal-footer">
					<button type="button" data-bs-dismiss="modal" class="btn btn-sm btn-secondary">{{ __('Cancel') }}</button>
					<button type="submit" data-bs-dismiss="modal" class="btn btn-sm btn-primary btn-confirm-password">{{ __('Confirm') }}</button>
				</div>
			</form>
		</div>
	</div>
</div>
