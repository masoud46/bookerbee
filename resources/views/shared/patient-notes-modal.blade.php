<div id="patient-notes-modal" class="modal fade" data-saved="1" tabindex="-1" aria-labelledby="patient-notes-modal-title" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-scrollable">
		<div class="modal-content shadow">
			<div class="modal-header shadow-sm">
				<h6 class="modal-title" id="patient-notes-modal-title">{{ __("Notes:") }}<span class="fw-bold"></span></h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body position-relative modal-is-waiting" tabindex="-1">
				<div class="modal-body-container">
					<div class="modal-notes-content form-control bg-transparent" contenteditable="true" data-empty="{{ __("There is no note.") }}"></div>
				</div>
				<div class="modal-waiting position-absolute top-0 start-0 bottom-0 end-0 d-flex justify-content-center align-items-center fs-1">
					<i class="fas fa-spinner fa-spin text-secondary opacity-25" style="--fa-animation-duration: 1s;"></i>
				</div>
			</div>
			<div class="modal-footer">
				<small class="flex-grow-1 text-black-50 fst-italic">{{ __("Click on the notes to edit them.") }}</small>
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __("Close") }}</button>
				<button type="button" class="btn btn-sm btn-primary btn-save-notes">{{ __("Save") }}</button>
			</div>
		</div>
	</div>
</div>
