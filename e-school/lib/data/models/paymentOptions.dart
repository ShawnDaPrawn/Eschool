class PaymentOptions {

  PaymentOptions();

  PaymentOptions.fromJson(Map<String, dynamic> json) {
    feesDueDate = json['fees_due_date'];
    feesDueCharges = json['fees_due_charges'];
    currencyCode = json['currency_code'];
    currencySymbol = json['currency_symbol'];
    razorpay = json['razorpay'] != null
        ? RazorpayOption.fromJson(json['razorpay'])
        : null;
    stripe = json['stripe'] != null ? Stripe.fromJson(json['stripe']) : null;
    paystack =
        json['paystack'] != null ? Paystack.fromJson(json['paystack']) : null;
    flutterwave = json['flutterwave'] != null
        ? Flutterwave.fromJson(json['flutterwave'])
        : null;
  }
  RazorpayOption? razorpay;
  Stripe? stripe;
  Paystack? paystack;
  Flutterwave? flutterwave;
  String? currencyCode;
  String? currencySymbol;
  String? feesDueDate;
  String? feesDueCharges;
}

class RazorpayOption {

  RazorpayOption();

  RazorpayOption.fromJson(Map<String, dynamic> json) {
    razorpayStatus = json['razorpay_status'];
    razorpayApiKey = json['razorpay_api_key'];
    razorpayWebhookSecret = json['razorpay_webhook_secret'];
    currencyCode = json['razorpay_currency_code'];
  }
  String? razorpayStatus;
  String? razorpayApiKey;
  String? razorpayWebhookSecret;
  String? currencyCode;
}

class Stripe {

  Stripe();

  Stripe.fromJson(Map<String, dynamic> json) {
    stripeStatus = json['stripe_status'];
    stripePublishableKey = json['stripe_publishable_key'];
    currencyCode = json['stripe_currency_code'];
  }
  String? stripeStatus;
  String? stripePublishableKey;
  String? currencyCode;
}

class Paystack {

  Paystack();

  Paystack.fromJson(Map<String, dynamic> json) {
    paystackStatus = json['paystack_status'];
    paystackPublicKey = json['paystack_public_key'];
    currencyCode = json['paystack_currency_code'];
  }
  String? paystackStatus;
  String? paystackPublicKey;
  String? currencyCode;
}

class Flutterwave {

  Flutterwave();

  Flutterwave.fromJson(Map<String, dynamic> json) {
    flutterwaveStatus = json['flutterwave_status'];
    flutterwavePublicKey = json['flutterwave_public_key'];
    currencyCode = json['flutterwave_currency_code'];
  }
  String? flutterwaveStatus;
  String? flutterwavePublicKey;
  String? currencyCode;
}
