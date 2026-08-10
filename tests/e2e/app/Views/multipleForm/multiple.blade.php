@extends($layout)

@section("content")
    <div id="billing-form" data-test="billing-form"></div>
    <div id="shipping-form" data-test="shipping-form"></div>

    <script>
        var billingForm = Z.Forms.create({
            dom: "billing-form",
            name: "billing",
        });

        billingForm.createField({
            name: "billing_value",
            type: "text",
        });

        var shippingForm = Z.Forms.create({
            dom: "shipping-form",
            name: "shipping",
        });

        shippingForm.createField({
            name: "shipping_value",
            type: "text",
        });
    </script>
@endsection
