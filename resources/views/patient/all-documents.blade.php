@extends('layouts.main_with_tabs')
@section('content')
    <div class="custom-block">
        <i class="edit-loader submit-button-loader custom-loader" style="display:none"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
    </div>
@endsection
@push('custom-scripts')
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
<script type="text/javascript">
    var private_key = '';
    $(document).ready(function() {
        var active = '{{ Request::get("type") }}';
        var organization_id = '{{ \Session::has('organization_id') ? decrypt(\Session::get('organization_id')) : '' }}';
        var redirect_url = "{{ url('scan-key') }}";
        var data = JSON.parse(localStorage.getItem('keys'));
        $.each(data, function(key, value) {
            if (organization_id == value.organization_id) {
                private_key = value.private_key;
                return 0;
            };
        });
        getAllPatients();

        // To get all the patients
        function getAllPatients() {
            $('.submit-button-loader').show();
            var url = "{{ url('patients/all') }}";
            $.ajax({
                type: "POST",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    private_key: private_key,
                },
                success: function(data) {
                    $('.submit-button-loader').hide();
                    $(".custom-block").html(data);
                },
                error: function(data) {
                    $('.submit-button-loader').hide();
                    window.location.href = redirect_url;
                }
            });
        }
    });
</script>
@endpush