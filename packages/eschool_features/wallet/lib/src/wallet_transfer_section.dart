import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';
import 'wallet_cubit.dart';
import 'wallet_text.dart';

class WalletTransferSection extends StatefulWidget {
  const WalletTransferSection({required this.cubit, super.key});
  final WalletCubit cubit;
  @override
  State<WalletTransferSection> createState() => _WalletTransferSectionState();
}

class _WalletTransferSectionState extends State<WalletTransferSection> {
  final _form = GlobalKey<FormState>();
  final _amount = TextEditingController();
  int? _child;
  bool _returning = false, _confirmed = false;
  @override
  void dispose() {
    _amount.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final cubit = widget.cubit;
    final state = cubit.state;
    final history = cubit.transfers;
    String tr(String text) => walletText(context, text);
    final own = state.index?.owners
        .where((o) => o.isSelf)
        .firstOrNull
        ?.wallets
        .firstOrNull;
    final children =
        state.index?.owners
            .where((o) => !o.isSelf && o.wallets.isNotEmpty)
            .toList() ??
        <WalletOwner>[];
    final selected =
        children.where((o) => o.ownerUserId == _child).firstOrNull ??
        children.firstOrNull;
    final child = selected?.wallets.firstOrNull;
    final returns =
        history?.childReturns ==
        WalletTransferPageChildReturnsEnum.currentApprovingGuardian;
    final returning = _returning && returns;
    final ready = !state.busy && !cubit.hasPendingMoney;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              tr('Parent wallet transfers'),
              style: Theme.of(context).textTheme.titleLarge,
            ),
            if (cubit.hasPendingMoney) ...[
              Text(
                tr(
                  'A previous wallet request needs to be resolved before another money movement.',
                ),
              ),
              OutlinedButton(
                onPressed: state.busy ? null : () => cubit.resolvePending(),
                child: Text(tr('Resolve interrupted wallet request')),
              ),
            ],
            if (cubit.resolutionMessage != null)
              Text(tr(cubit.resolutionMessage!)),
            if (cubit.lastTransfer case final receipt?)
              Text(
                '${tr('Transfer receipt')}: ${receipt.reference} · ${walletAmount(receipt.currency, receipt.amountCents)}',
              ),
            if (history?.canTransfer == true && own != null && child != null)
              Form(
                key: _form,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    DropdownButtonFormField<int>(
                      initialValue: selected!.ownerUserId,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: tr('Child wallet'),
                      ),
                      items: [
                        for (final owner in children)
                          DropdownMenuItem(
                            value: owner.ownerUserId,
                            child: Text(
                              owner.ownerName,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                      ],
                      onChanged: ready
                          ? (value) => setState(() {
                              _child = value;
                              _confirmed = false;
                            })
                          : null,
                    ),
                    if (returns)
                      SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(tr('Return child funds to my wallet')),
                        value: returning,
                        onChanged: ready
                            ? (value) => setState(() {
                                _returning = value;
                                _confirmed = false;
                              })
                            : null,
                      )
                    else
                      Text(
                        tr('The school has not enabled child wallet returns.'),
                      ),
                    Text(
                      '${tr('From')}: ${returning ? selected.ownerName : own.ownerName}',
                    ),
                    Text(
                      '${tr('To')}: ${returning ? own.ownerName : selected.ownerName}',
                    ),
                    TextFormField(
                      controller: _amount,
                      enabled: ready,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: InputDecoration(
                        labelText: tr('Wallet transfer amount'),
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
                    CheckboxListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(
                        tr(
                          'I reviewed the source, recipient and amount for this wallet transfer.',
                        ),
                      ),
                      value: _confirmed,
                      onChanged: ready
                          ? (value) =>
                                setState(() => _confirmed = value ?? false)
                          : null,
                    ),
                    FilledButton(
                      onPressed: ready && _confirmed
                          ? () async {
                              if (!_form.currentState!.validate()) return;
                              final cents = walletCents(_amount.text)!;
                              final done = await cubit.transfer(
                                returning ? child.id : own.id,
                                returning ? own.id : child.id,
                                cents,
                              );
                              if (mounted && done) {
                                setState(() {
                                  _amount.clear();
                                  _confirmed = false;
                                });
                              }
                            }
                          : null,
                      child: Text(tr('Move wallet money')),
                    ),
                  ],
                ),
              ),
            if (history != null && history.items.isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(
                tr('Your transfer receipts'),
                style: Theme.of(context).textTheme.titleMedium,
              ),
              for (final receipt in history.items)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(receipt.reference),
                  subtitle: Text(
                    '${tr(receipt.kind == WalletTransferReceiptKindEnum.toChild ? 'To child wallet' : 'To my wallet')} · ${walletAmount(receipt.currency, receipt.amountCents)}',
                  ),
                ),
              Wrap(
                spacing: 8,
                children: [
                  if (history.currentPage > 1)
                    TextButton(
                      onPressed: state.busy
                          ? null
                          : () => cubit.transferHistory(
                              page: history.currentPage - 1,
                            ),
                      child: Text(tr('Previous transfer receipts')),
                    ),
                  if (history.currentPage * history.perPage < history.total)
                    TextButton(
                      onPressed: state.busy
                          ? null
                          : () => cubit.transferHistory(
                              page: history.currentPage + 1,
                            ),
                      child: Text(tr('More transfer receipts')),
                    ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
