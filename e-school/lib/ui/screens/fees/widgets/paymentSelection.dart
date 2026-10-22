// ignore_for_file: must_be_immutable

import 'package:eschool/data/models/paymentOptions.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class PaymentSelectionBottomSheet extends StatefulWidget {
  PaymentSelectionBottomSheet({required this.enabledPayments, super.key});
  PaymentOptions enabledPayments;

  @override
  PaymentSelectionBottomSheetStateState createState() =>
      PaymentSelectionBottomSheetStateState();
}

class PaymentSelectionBottomSheetStateState
    extends State<PaymentSelectionBottomSheet> {
  // List of items
  List<String> options = [];

  String? selectedPaymentOption;

  @override
  void initState() {
    if (widget.enabledPayments.stripe != null) options.add('stripe');
    if (widget.enabledPayments.paystack != null) options.add('paystack');
    if (widget.enabledPayments.flutterwave != null) options.add('flutterwave');

    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(12),
          topRight: Radius.circular(12),
        ),
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SizedBox(height: 8),
          Text(
            UiUtils.getTranslatedLabel(context, paymentOptionsKey),
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          Padding(padding: const EdgeInsets.all(8.0), child: const Divider()),
          ListView.builder(
            shrinkWrap:
                true, // Important to allow the list to fit in the BottomSheet
            itemCount: options.length,
            itemBuilder: (context, index) {
              return ListTile(
                title: Text(options[index].toCapitalized()),
                selected: selectedPaymentOption == options[index],
                trailing: Icon(Icons.arrow_forward_ios_rounded, size: 20),
                onTap: () {
                  setState(() => selectedPaymentOption = options[index]);
                  // Close BottomSheet after selection
                  Navigator.pop(context, selectedPaymentOption);
                },
              );
            },
          ),
        ],
      ),
    );
  }
}
