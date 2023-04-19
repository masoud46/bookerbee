<div id="patient-notes-modal" class="modal fade" data-saved="1" tabindex="-1" aria-labelledby="patient-notes-modal-title" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-scrollable">
		<div class="modal-content shadow">
			<div class="modal-header bg-dark bg-opacity-10">
				<h6 class="modal-title d-flex flex-wrap" id="patient-notes-modal-title">
					<span><i class="far fa-user fa-fw me-1"></i><span class="patient-notes-modal-name fw-bold me-3"></span></span>
					<span><i class="far fa-envelope fa-fw me-1"></i><a class="patient-notes-modal-email me-3"></a></span>
					<span><i class="fas fa-mobile-screen-button fa-fw me-1"></i><span class="patient-notes-modal-phone"></span></span>
				</h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body position-relative modal-is-waiting" tabindex="-1">
				<div class="modal-body-container">
					<div class="modal-notes-content form-control bg-transparent" contenteditable="true" data-empty="{{ __('There is no note.') }}"></div>
				</div>
				<div class="modal-waiting position-absolute top-0 start-0 bottom-0 end-0 d-flex justify-content-center align-items-center fs-1">
					<i class="fas fa-spinner fa-spin text-secondary opacity-25" style="--fa-animation-duration: 1s;"></i>
				</div>
			</div>
			<div class="modal-footer">
				<small class="flex-grow-1 text-black-50 fst-italic">{{ __('Click on the notes to edit them.') }}</small>
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
				<button type="button" class="btn btn-sm btn-primary btn-save-notes">{{ __('Save') }}</button>
			</div>
		</div>
	</div>
</div>
