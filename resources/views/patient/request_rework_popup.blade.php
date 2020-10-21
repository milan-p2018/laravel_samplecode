{{-- request for rework modal --}}
<div class="modal-header">
    <h2>{{__('lang.request_revision')}}</h2>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true"><i><img src="{{asset('assets/images/cross-icon.svg')}}"></i></span>
    </button>
</div>
<form method="POST"action="{{ url('rework-request') }}" name="requestForReworkForm" id="requestForReworkForm">
    @csrf
    <div class="modal-wrap">
        <div class="modal-container">
            <div class="request-revision-form">
                <div class="form-group">
                    <label>{{__('lang.subject')}}</label>
                    <input id="reworkSubject" name="subject" type="text" class="form-control">
                    <span class="error-msg"></span>
                </div>
                <div class="form-group">
                    <label>{{__('lang.description')}}</label>
                    <textarea id="reworkDescription" name="description" class="form-control"></textarea>
                    <span class="error-msg"></span>
                </div>
                <input hidden name="request_type" value="1" />
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <div class="btn-wrap">
            <a href="#" title="" class="btn btn-green submit-form">{{__('lang.submit')}}</a>
            <a href="#" title="" class="btn btn-transparent btn-lg close" data-dismiss="modal" aria-label="Close">{{__('lang.abort')}}</a>
        </div>
    </div>
</form>

<script src="{{ asset('assets/js/custom.js') }}"></script>
<script type="text/javascript">
    //submit form
    console.log('here');
    $(".submit-form").on("click", function(event) {
        console.log('form submit');
        console.log($('#requestForReworkForm').serialize());
        if ($("#requestForReworkForm").valid()) {
            $("#requestForReworkForm").submit();
        }
    });

</script>