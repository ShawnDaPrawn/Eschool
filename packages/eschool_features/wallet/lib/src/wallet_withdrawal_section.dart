import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';
import 'wallet_cubit.dart';
import 'wallet_text.dart';

class WalletWithdrawalSection extends StatefulWidget {
  const WalletWithdrawalSection({required this.cubit, super.key});
  final WalletCubit cubit;
  @override
  State<WalletWithdrawalSection> createState() =>
      _WalletWithdrawalSectionState();
}

class _WalletWithdrawalSectionState extends State<WalletWithdrawalSection> {
  final _form = GlobalKey<FormState>();
  final _amount = TextEditingController();
  final _bank = TextEditingController();
  int? _slot;
  bool _confirmed = false;
  @override
  void dispose() {
    _amount.dispose();
    _bank.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final cubit = widget.cubit;
    final page = cubit.withdrawals;
    if (page == null) return const SizedBox.shrink();
    String tr(String text) => walletText(context, text);
    final ready = !cubit.state.busy && !cubit.hasPendingMoney;
    final own = cubit.state.index?.owners
        .where((o) => o.isSelf)
        .firstOrNull
        ?.wallets
        .firstOrNull;
    final cents = walletCents(_amount.text);
    final configured = page.cashThresholdCents != null;
    final cash =
        configured &&
        cents != null &&
        (cents < page.cashThresholdCents! ||
            (cents == page.cashThresholdCents && page.cashAtBoundary!));
    final selected = page.slots.any((s) => s.id == _slot) ? _slot : null;
    String status(WalletWithdrawalReceiptStatusEnum value) => switch (value) {
      WalletWithdrawalReceiptStatusEnum.approved =>
        'Approved, awaiting actual payment',
      WalletWithdrawalReceiptStatusEnum.paid => 'Paid',
      WalletWithdrawalReceiptStatusEnum.cancelled => 'Cancelled',
      WalletWithdrawalReceiptStatusEnum.rejected => 'Rejected',
      _ => 'Awaiting Finance approval',
    };
    return Card(
      child: ExpansionTile(
        title: Text(tr('Adult wallet withdrawals')),
        childrenPadding: const EdgeInsets.all(16),
        expandedCrossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            tr(
              'Withdraw only your own available wallet money. Requests reserve the amount until Finance approves and verifies actual payment. Learners cannot withdraw.',
            ),
          ),
          if (own != null)
            Text(
              '${tr('Available wallet balance')}: ${walletAmount(own.currency, own.balanceCents)}',
            ),
          if (page.readOnly) Text(tr('Wallet history is read-only.')),
          if (page.isAdult && !page.canRequest && !page.readOnly)
            Text(
              tr(
                'Finance has not enabled withdrawals, or you need to open your own wallet first. Existing wallet activity can continue.',
              ),
            ),
          Text(
            tr(
              'You can cancel before Finance approval. After approval, contact Finance before changing the request.',
            ),
          ),
          if (configured) ...[
            Text(
              '${tr('Cash-or-bank boundary')}: ${walletAmount(page.currency!, page.cashThresholdCents!)}',
            ),
            Text(
              '${tr('Exactly the boundary uses')}: ${tr(page.cashAtBoundary! ? 'Cash pickup' : 'Bank EFT')}',
            ),
            Text(
              tr(
                'Amounts below the boundary use cash pickup; larger amounts use bank EFT.',
              ),
            ),
          ],
          if (cubit.lastWithdrawal case final receipt?)
            Text('${tr('Withdrawal request recorded')}: ${receipt.reference}'),
          if (page.canRequest)
            Form(
              key: _form,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  TextFormField(
                    controller: _amount,
                    enabled: ready,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: InputDecoration(
                      labelText: tr('Withdrawal amount'),
                    ),
                    validator: (text) {
                      final amount = walletCents(text ?? '');
                      if (amount == null || amount < 1) {
                        return tr(
                          'Enter a positive amount with at most two decimal places.',
                        );
                      }
                      if (own == null || amount > own.balanceCents) {
                        return tr(
                          'Use an amount within your available wallet balance.',
                        );
                      }
                      return null;
                    },
                    onChanged: (_) => setState(() {
                      _confirmed = false;
                      _slot = null;
                      _bank.clear();
                    }),
                  ),
                  if ((cents ?? 0) > 0) ...[
                    Text(
                      '${tr('Withdrawal method')}: ${tr(cash ? 'Cash pickup' : 'Bank EFT')}',
                    ),
                    if (cash) ...[
                      if (page.slots.isEmpty)
                        Text(
                          tr(
                            'No cash pickup slots are available. Contact Finance.',
                          ),
                        ),
                      DropdownButtonFormField<int>(
                        key: ValueKey(
                          '${page.slots.map((s) => s.id).join(',')}:$selected',
                        ),
                        initialValue: selected,
                        isExpanded: true,
                        itemHeight: 72,
                        decoration: InputDecoration(
                          labelText: tr('Cash pickup slot'),
                        ),
                        items: [
                          for (final slot in page.slots)
                            DropdownMenuItem(
                              value: slot.id,
                              child: Text(
                                slot.label,
                                maxLines: 3,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                        ],
                        selectedItemBuilder: (_) => [
                          for (final slot in page.slots)
                            Text(
                              slot.label,
                              maxLines: 3,
                              overflow: TextOverflow.ellipsis,
                            ),
                        ],
                        onChanged: ready
                            ? (value) => setState(() {
                                _slot = value;
                                _confirmed = false;
                              })
                            : null,
                        validator: (value) =>
                            value != null &&
                                page.slots.any((s) => s.id == value)
                            ? null
                            : tr('Choose an available cash pickup slot.'),
                      ),
                    ] else
                      TextFormField(
                        controller: _bank,
                        enabled: ready,
                        minLines: 2,
                        maxLines: 4,
                        maxLength: 2000,
                        decoration: InputDecoration(
                          labelText: tr(
                            'Private bank details for your own account',
                          ),
                        ),
                        validator: (text) => (text ?? '').trim().length >= 10
                            ? null
                            : tr(
                                'Provide bank instructions for your own account only.',
                              ),
                        onChanged: (_) {
                          if (_confirmed) setState(() => _confirmed = false);
                        },
                      ),
                  ],
                  CheckboxListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(
                      tr(
                        'I reviewed the amount and my own pickup or bank details. Reserve these funds for Finance approval; this does not send a payment.',
                      ),
                    ),
                    value: _confirmed,
                    onChanged: ready
                        ? (value) => setState(() => _confirmed = value ?? false)
                        : null,
                  ),
                  FilledButton(
                    onPressed:
                        ready && _confirmed && (!cash || selected != null)
                        ? () async {
                            if (!_form.currentState!.validate()) return;
                            final done = await cubit.requestWithdrawal(
                              amountCents: walletCents(_amount.text)!,
                              slotId: cash ? selected : null,
                              bankInstructions: cash ? null : _bank.text,
                            );
                            if (mounted && done) {
                              setState(() {
                                _amount.clear();
                                _bank.clear();
                                _confirmed = false;
                                _slot = null;
                              });
                            }
                          }
                        : null,
                    child: Text(tr('Request wallet withdrawal')),
                  ),
                ],
              ),
            ),
          const SizedBox(height: 12),
          Text(
            tr('Your withdrawal requests and receipts'),
            style: Theme.of(context).textTheme.titleMedium,
          ),
          for (final row in page.items)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    row.reference,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  Text(walletAmount(row.currency, row.amountCents)),
                  Text(tr(status(row.status))),
                  if (row.pickupLabel case final label?)
                    Text('${tr('Cash pickup')}: $label'),
                  if (row.payoutReference case final reference?)
                    Text(reference),
                  if (row.paidOn case final date?)
                    Text(
                      '${tr('Paid date')}: ${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}',
                    ),
                  if (page.isAdult &&
                      !page.readOnly &&
                      row.status == WalletWithdrawalReceiptStatusEnum.requested)
                    TextButton(
                      onPressed: ready
                          ? () async {
                              final confirmed = await showDialog<bool>(
                                context: context,
                                builder: (dialogContext) => AlertDialog(
                                  title: Text(
                                    tr('Cancel this unpaid withdrawal?'),
                                  ),
                                  content: Text(
                                    tr(
                                      'Only cancel if no outside payment has been made. The reserved amount returns to your original wallet.',
                                    ),
                                  ),
                                  actions: [
                                    TextButton(
                                      onPressed: () =>
                                          Navigator.pop(dialogContext, false),
                                      child: Text(tr('Keep withdrawal')),
                                    ),
                                    FilledButton(
                                      onPressed: () =>
                                          Navigator.pop(dialogContext, true),
                                      child: Text(
                                        tr('Cancel unpaid withdrawal'),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                              if (confirmed == true && mounted) {
                                await cubit.cancelWithdrawal(row.id);
                              }
                            }
                          : null,
                      child: Text(tr('Cancel unpaid withdrawal')),
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
                      ? () =>
                            cubit.withdrawalHistory(page: page.currentPage - 1)
                      : null,
                  child: Text(tr('Previous withdrawal receipts')),
                ),
              if (page.currentPage * page.perPage < page.total)
                TextButton(
                  onPressed: ready
                      ? () =>
                            cubit.withdrawalHistory(page: page.currentPage + 1)
                      : null,
                  child: Text(tr('More withdrawal receipts')),
                ),
            ],
          ),
        ],
      ),
    );
  }
}
