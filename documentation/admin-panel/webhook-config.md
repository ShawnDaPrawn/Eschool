---
sidebar_position: 10
---

# Payment Gateway's Webhook Configuration

Stripe is the payment gateway for online fee payments. A payment is only marked as paid when Stripe calls the webhook URL shown on the Fees Configuration page, so set the webhook up before switching Stripe on.

![Webhook Overview](../static/images/admin/14.jpg)

## Webhook Configuration for Stripe
![Stripe Webhook 1](../static/images/admin/18.jpg)
![Stripe Webhook 2](../static/images/admin/19.jpg)
![Stripe Webhook 3](../static/images/admin/20.jpg)

Copy the signing secret Stripe shows for the webhook into **Stripe Webhook Secret** on the Fees Configuration page. Without it every payment stays pending.

Razorpay, Paystack and Flutterwave are no longer supported, and their webhook URLs no longer exist.
