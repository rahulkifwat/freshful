@extends('layout.master')
@section('content')

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="d-flex align-items-center mb-4 gap-3">
                    <a href="{{ route('myaccount') }}" class="btn btn-outline-secondary btn-sm">← Back</a>
                    <h5 class="mb-0 fw-bold">Cancel Order</h5>
                </div>

                <div class="card shadow-sm p-4">
                    <p>Dear valued customer,</p>
                    <p>We're sorry to hear that you wish to cancel your order. Your feedback is important to us — please select a reason for the cancellation so we can improve our service.</p>

                    <form id="cancel-form">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order_id }}">

                        @php
                        $reasons = [
                            'Personal reasons',
                            'Delay in delivery',
                            'Customer service issues',
                            'Changed mind',
                            'Delivery boy unable to locate my address',
                            'Unexpected emergency',
                            'Delivery boy was rude or unprofessional',
                            'Delivery boy got stuck in traffic',
                            'Unable to contact delivery boy',
                            'Need to reschedule the order',
                            'Wrong items ordered',
                            'Want to change the item',
                            'Product out of stock',
                            'Price too high',
                            'Item received damaged or defective',
                            'Wrong item received',
                            'Concerns about quality',
                            'Credit card declined',
                            'Delivery time not suitable',
                        ];
                        @endphp

                        @foreach($reasons as $reason)
                        <div class="mb-2">
                            <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                                <input type="radio" name="reason_for_order_cancel" value="{{ $reason }}">
                                <span>{{ $reason }}</span>
                            </label>
                        </div>
                        @endforeach

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-danger px-4 fw-bold">Submit Cancellation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

@push('scripts')
<script>
$('#cancel-form').on('submit', function (e) {
    e.preventDefault();
    var reason = $('input[name=reason_for_order_cancel]:checked').val();
    if (!reason) { notify('Please select a reason', 'warning'); return; }

    $.post('{{ route("cancel-order.ajax") }}', {
        _token: '{{ csrf_token() }}',
        order_id: $('[name=order_id]').val(),
        reason_for_order_cancel: reason
    }, function (res) {
        if (res.result) {
            notify(res.message, 'success');
            setTimeout(function () { window.location.href = '{{ route("myaccount") }}'; }, 1500);
        } else {
            notify(res.message, 'error');
        }
    }, 'json').fail(function () {
        notify('Network error', 'error');
    });
});
</script>
@endpush

@endsection
