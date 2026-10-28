import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';
import 'wallet_cubit.dart';
import 'wallet_text.dart';

class WalletDepositSection extends StatefulWidget {
  const WalletDepositSection({required this.cubit, super.key});
  final WalletCubit cubit;
  @override
  State<WalletDepositSection> createState() => _WalletDepositSectionState();
}

class _WalletDepositSectionState extends State<WalletDepositSection> {
  final _form = GlobalKey<FormState>();
  final _amount = TextEditingController();
  final _reference = TextEditingController();
  final _proof = TextEditingController();
  final _date = TextEditingController();
  String? _method;
  bool _confirmed = false;

  @override
  void initState() {
    super.initState();
    _date.text = _day(DateTime.now());
  }

  String _day(DateTime value) =>
      '${value.year.toString().padLeft(4, '0')}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';

  @override
  void dispose() {
    for (final controller in [_amount, _reference, _proof, _date]) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final cubit = widget.cubit;
    final page = cubit.deposits;
    if (page == null) return const SizedBox.shrink();
    String tr(String text) => walletText(context, text);
    final ready = !cubit.state.busy && !cubit.hasPendingMoney;
    final methods = [if (page.cashAllowed) 'cash', if (page.eftAllowed) 'eft'];
    final selected = methods.contains(_method) ? _method : null;
    String reason(WalletDepositReceiptReasonCodeEnum? value) => switch (value) {
      WalletDepositReceiptReasonCodeEnum.notVerified =>
        'Receipt could not be verified',
      WalletDepositReceiptReasonCodeEnum.duplicate =>
        'Receipt was already recorded',
      WalletDepositReceiptReasonCodeEnum.requestIncorrect =>
        'Request details need correction',
      _ => '',
    };
    return Card(
      child: ExpansionTile(
        title: Text(tr('Parent wallet deposits')),
        childrenPadding: const EdgeInsets.all(16),
        expandedCrossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            tr(
              'Request verification only after paying cash or EFT. Finance checks the actual receipt before your wallet balance changes.',
            ),
          ),
          if (page.readOnly) Text(tr('Wallet history is read-only.')),
          if (page.isParent && !page.canSubmit && !page.readOnly)
            Text(
              tr(
                'Finance has not enabled parent deposit methods. Existing wallet transfers can continue.',
              ),
            ),
          if (cubit.lastDeposit case final receipt?)
            Text('${tr('Deposit request recorded')}: ${receipt.reference}'),
          if (page.canSubmit)
            Form(
              key: _form,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  DropdownButtonFormField<String>(
                    initialValue: selected,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: tr('Deposit method'),
                    ),
                    items: [
                      for (final method in methods)
                        DropdownMenuItem(
                          value: method,
                          child: Text(
                            tr(
                              method == 'cash'
                                  ? 'Cash already paid'
                                  : 'EFT already paid',
                            ),
                          ),
                        ),
                    ],
                    onChanged: ready
                        ? (value) => setState(() {
                            _method = value;
                            _confirmed = false;
                          })
                        : null,
                    validator: (value) =>
                        value != null && methods.contains(value)
                        ? null
                        : tr('Choose the method actually paid.'),
                  ),
                  TextFormField(
                    controller: _amount,
                    enabled: ready,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: InputDecoration(
                      labelText: tr('Amount already paid'),
                    ),
                    validator: (value) => (walletCents(value ?? '') ?? 0) > 0
                        ? null
                        : tr(
                            'Enter a positive amount with at most two decimal places.',
                          ),
                    onChanged: (_) {
                      if (_confirmed) setState(() => _confirmed = false);
                    },
                  ),
                  TextFormField(
                    controller: _reference,
                    enabled: ready,
                    maxLength: 96,
                    decoration: InputDecoration(
                      labelText: tr(
                        'Cash receipt or bank transaction reference',
                      ),
                    ),
                    validator: (value) =>
                        RegExp(
                          r'^[A-Za-z0-9][A-Za-z0-9 ._:/-]{0,95}$',
                        ).hasMatch((value ?? '').trim())
                        ? null
                        : tr('Enter the receipt or transaction reference.'),
                    onChanged: (_) {
                      if (_confirmed) setState(() => _confirmed = false);
                    },
                  ),
                  TextFormField(
                    controller: _date,
                    enabled: ready,
                    keyboardType: TextInputType.datetime,
                    decoration: InputDecoration(
                      labelText: tr('Paid date (YYYY-MM-DD)'),
                    ),
                    validator: (value) {
                      final date = DateTime.tryParse(value ?? '');
                      return date != null && _day(date) == value
                          ? null
                          : tr('Enter the actual paid date as YYYY-MM-DD.');
                    },
                    onChanged: (_) {
                      if (_confirmed) setState(() => _confirmed = false);
                    },
                  ),
                  TextFormField(
                    controller: _proof,
                    enabled: ready,
                    maxLines: 3,
                    maxLength: 2000,
                    decoration: InputDecoration(
                      labelText: tr('Private receipt details for Finance'),
                    ),
                    validator: (value) => (value ?? '').trim().length >= 10
                        ? null
                        : tr(
                            'Add enough receipt details for Finance to verify the deposit.',
                          ),
                    onChanged: (_) {
                      if (_confirmed) setState(() => _confirmed = false);
                    },
                  ),
                  CheckboxListTile(
                    contentPadding: EdgeInsets.zero,
                    value: _confirmed,
                    title: Text(
                      tr(
                        'I already paid this deposit and understand that Finance must verify it before crediting my wallet.',
                      ),
                    ),
                    onChanged: ready
                        ? (value) => setState(() => _confirmed = value ?? false)
                        : null,
                  ),
                  FilledButton(
                    onPressed: ready && _confirmed
                        ? () async {
                            if (!_form.currentState!.validate() ||
                                selected == null) {
                              return;
                            }
                            final done = await cubit.requestDeposit(
                              amountCents: walletCents(_amount.text)!,
                              method: selected,
                              reference: _reference.text,
                              paidOn: _date.text,
                              evidence: _proof.text,
                            );
                            if (mounted && done) {
                              setState(() {
                                _amount.clear();
                                _reference.clear();
                                _proof.clear();
                                _confirmed = false;
                              });
                            }
                          }
                        : null,
                    child: Text(tr('Submit deposit for verification')),
                  ),
                ],
              ),
            ),
          const SizedBox(height: 12),
          Text(
            tr('Your deposit requests and receipts'),
            style: Theme.of(context).textTheme.titleMedium,
          ),
          for (final receipt in page.items)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    receipt.reference,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  Text(
                    '${tr('Requested amount')}: ${walletAmount(receipt.currency, receipt.requestedCents)}',
                  ),
                  Text(
                    tr(switch (receipt.status) {
                      WalletDepositReceiptStatusEnum.verified => 'Verified',
                      WalletDepositReceiptStatusEnum.cancelled => 'Cancelled',
                      WalletDepositReceiptStatusEnum.rejected => 'Rejected',
                      _ => 'Awaiting Finance verification',
                    }),
                  ),
                  if (reason(receipt.reasonCode).isNotEmpty)
                    Text(tr(reason(receipt.reasonCode))),
                  if (receipt.verifiedReceipt case final verified?) ...[
                    Text(verified.reference),
                    Text(
                      '${tr('Actual verified amount')}: ${walletAmount(verified.currency, verified.amountCents)}',
                    ),
                  ],
                  if (page.isParent &&
                      !page.readOnly &&
                      receipt.status == WalletDepositReceiptStatusEnum.pending)
                    TextButton(
                      onPressed: ready
                          ? () async {
                              final confirmed = await showDialog<bool>(
                                context: context,
                                builder: (dialogContext) => AlertDialog(
                                  title: Text(
                                    tr('Withdraw this verification request?'),
                                  ),
                                  content: Text(
                                    tr(
                                      'Withdrawing a pending request moves no bank or wallet money.',
                                    ),
                                  ),
                                  actions: [
                                    TextButton(
                                      onPressed: () =>
                                          Navigator.pop(dialogContext, false),
                                      child: Text(tr('Keep request')),
                                    ),
                                    FilledButton(
                                      onPressed: () =>
                                          Navigator.pop(dialogContext, true),
                                      child: Text(tr('Withdraw request')),
                                    ),
                                  ],
                                ),
                              );
                              if (confirmed == true && mounted) {
                                await cubit.cancelDeposit(receipt.id);
                              }
                            }
                          : null,
                      child: Text(tr('Withdraw pending request')),
                    ),
                ],
              ),
            ),
          Wrap(
            spacing: 8,
            children: [
              if (page.currentPage > 1)
                TextButton(
                  onPressed: ready
                      ? () => cubit.depositHistory(page: page.currentPage - 1)
                      : null,
                  child: Text(tr('Previous deposit receipts')),
                ),
              if (page.currentPage * page.perPage < page.total)
                TextButton(
                  onPressed: ready
                      ? () => cubit.depositHistory(page: page.currentPage + 1)
                      : null,
                  child: Text(tr('More deposit receipts')),
                ),
            ],
          ),
        ],
      ),
    );
  }
}
