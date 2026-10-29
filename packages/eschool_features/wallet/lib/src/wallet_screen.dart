import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'wallet_cubit.dart';
import 'wallet_repository.dart';
import 'wallet_text.dart';
import 'wallet_transfer_section.dart';
import 'wallet_deposit_section.dart';
import 'wallet_withdrawal_section.dart';

class WalletScreen extends StatelessWidget {
  const WalletScreen({
    required this.repository,
    this.storefrontBuilder,
    super.key,
  });
  final WalletRepository repository;
  final WidgetBuilder? storefrontBuilder;
  @override
  Widget build(BuildContext context) => BlocProvider(
    create: (_) => WalletCubit(repository)..load(),
    child: _WalletView(storefrontBuilder: storefrontBuilder),
  );
}

class _WalletView extends StatefulWidget {
  const _WalletView({this.storefrontBuilder});
  final WidgetBuilder? storefrontBuilder;
  @override
  State<_WalletView> createState() => _WalletViewState();
}

class _WalletViewState extends State<_WalletView> {
  bool editing = false;
  @override
  Widget build(BuildContext context) => BlocBuilder<WalletCubit, WalletState>(
    builder: (context, state) {
      final cubit = context.read<WalletCubit>();
      final statement = state.statement;
      String tr(String s) => walletText(context, s);
      return ScreenScaffold(
        title: tr('Wallets'),
        action: IconButton(
          tooltip: tr('Refresh wallets'),
          onPressed: state.busy
              ? null
              : () {
                  setState(() => editing = false);
                  cubit.load();
                },
          icon: const Icon(Icons.refresh),
        ),
        body: state.accessLost
            ? Center(
                child: Text(tr('Your wallet access changed. Reopen Wallets.')),
              )
            : ListView(
                key: const ValueKey('wallet-screen-scroll'),
                padding: const EdgeInsets.all(16),
                children: [
                  if (state.busy)
                    LinearProgressIndicator(
                      semanticsLabel: tr('Loading wallets'),
                    ),
                  if (state.error != null)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(tr(state.error!)),
                            if (cubit.canRetry)
                              TextButton(
                                onPressed: state.busy
                                    ? null
                                    : () => cubit.retry(),
                                child: Text(
                                  tr('Retry the same wallet request'),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  if (widget.storefrontBuilder != null)
                    FilledButton.tonalIcon(
                      onPressed: state.busy
                          ? null
                          : () async {
                              await Navigator.of(context).push(
                                MaterialPageRoute<void>(
                                  builder: widget.storefrontBuilder!,
                                ),
                              );
                              if (mounted) cubit.load();
                            },
                      icon: const Icon(Icons.storefront_outlined),
                      label: Text(tr('Shop and orders')),
                    ),
                  if (cubit.hasPendingMoney ||
                      cubit.transfers?.canTransfer == true ||
                      (cubit.transfers?.total ?? 0) > 0)
                    WalletTransferSection(cubit: cubit),
                  if (cubit.deposits?.isParent == true ||
                      (cubit.deposits?.total ?? 0) > 0)
                    WalletDepositSection(cubit: cubit),
                  if (cubit.withdrawals?.isAdult == true ||
                      (cubit.withdrawals?.total ?? 0) > 0)
                    WalletWithdrawalSection(cubit: cubit),
                  if (statement == null) ...[
                    Text(
                      tr('Your own and authorised child wallets'),
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    Text(
                      tr(
                        'Wallet money is separate from school fees. Opening a wallet adds no money.',
                      ),
                    ),
                    if (state.index != null && state.index!.currency == null)
                      Text(tr('The school has not configured wallets yet.')),
                    for (final owner in state.index?.owners ?? <WalletOwner>[])
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                owner.ownerName,
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                              if (owner.wallets.isEmpty) ...[
                                Text(tr('No wallet is open.')),
                                if (owner.canOpen)
                                  FilledButton(
                                    onPressed: state.busy
                                        ? null
                                        : () => cubit.open(owner.ownerUserId),
                                    child: Text(tr('Open wallet')),
                                  ),
                              ],
                              for (final wallet in owner.wallets) ...[
                                Text(
                                  walletAmount(
                                    wallet.currency,
                                    wallet.balanceCents,
                                  ),
                                  style: Theme.of(
                                    context,
                                  ).textTheme.headlineSmall,
                                ),
                                if (wallet.readOnly)
                                  Text(tr('Wallet history is read-only.')),
                                OutlinedButton(
                                  key: ValueKey(
                                    'wallet-statement-${wallet.id}',
                                  ),
                                  onPressed: state.busy
                                      ? null
                                      : () => cubit.show(wallet.id),
                                  child: Text(tr('Open wallet statement')),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                  ] else ...[
                    TextButton.icon(
                      onPressed: state.busy
                          ? null
                          : () {
                              setState(() => editing = false);
                              cubit.load();
                            },
                      icon: const Icon(Icons.arrow_back),
                      label: Text(tr('All your wallets')),
                    ),
                    Text(
                      statement.ownerName,
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    _amount(
                      context,
                      'Wallet balance',
                      statement.balanceCents,
                      statement.currency,
                    ),
                    if (statement.readOnly)
                      Text(tr('Wallet history is read-only.')),
                    _amount(
                      context,
                      'Effective daily spending cap',
                      statement.limits.effectiveDailySpendCents,
                      statement.currency,
                    ),
                    if (statement.isChild &&
                        statement.childLimitsRequired &&
                        !statement.limits.configured)
                      Text(
                        tr(
                          'A guardian must set spending controls before purchases.',
                        ),
                      ),
                    Text(
                      tr(
                        statement.limits.allCategories
                            ? 'All vendor categories permitted'
                            : 'Only selected vendor categories permitted',
                      ),
                    ),
                    if (!statement.limits.allCategories)
                      Text(statement.limits.categories.join(', ')),
                    if (statement.canManageLimits &&
                        !statement.readOnly &&
                        !editing)
                      OutlinedButton(
                        onPressed: state.busy
                            ? null
                            : () => setState(() => editing = true),
                        child: Text(tr('Edit spending controls')),
                      ),
                    if (editing &&
                        statement.canManageLimits &&
                        !statement.readOnly)
                      _Controls(
                        key: ValueKey(
                          '${statement.id}:${statement.limits.revision}',
                        ),
                        statement: statement,
                        busy: state.busy,
                        onSave: (daily, all, categories) async {
                          if (await cubit.controls(
                            statement,
                            daily,
                            all,
                            categories,
                          )) {
                            if (mounted) setState(() => editing = false);
                          }
                        },
                        onCancel: () => setState(() => editing = false),
                      ),
                    OutlinedButton(
                      onPressed: state.busy
                          ? null
                          : () {
                              setState(() => editing = false);
                              if (state.deskReceipts == null) {
                                cubit.deskReceipts(statement.id);
                              } else {
                                cubit.hideDeskReceipts();
                              }
                            },
                      child: Text(
                        tr(
                          state.deskReceipts == null
                              ? 'View desk top-up receipts'
                              : 'Hide desk top-up receipts',
                        ),
                      ),
                    ),
                    if (state.deskReceipts != null)
                      _DeskReceipts(
                        page: state.deskReceipts!,
                        busy: state.busy,
                        onPage: (page) =>
                            cubit.deskReceipts(statement.id, page: page),
                      ),
                    Text(
                      tr('Wallet statement'),
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    if (statement.entries.items.isEmpty)
                      Text(tr('No wallet movements yet.')),
                    for (final entry in statement.entries.items)
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(tr(entry.description)),
                              Text(entry.postedAt.toLocal().toString()),
                              _amount(
                                context,
                                'Amount',
                                entry.changeCents,
                                statement.currency,
                              ),
                              _amount(
                                context,
                                'Balance afterwards',
                                entry.balanceAfterCents,
                                statement.currency,
                              ),
                            ],
                          ),
                        ),
                      ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        TextButton(
                          onPressed:
                              state.busy || statement.entries.currentPage <= 1
                              ? null
                              : () => cubit.show(
                                  statement.id,
                                  page: statement.entries.currentPage - 1,
                                ),
                          child: Text(tr('Previous')),
                        ),
                        TextButton(
                          onPressed:
                              state.busy ||
                                  statement.entries.currentPage *
                                          statement.entries.perPage >=
                                      statement.entries.total
                              ? null
                              : () => cubit.show(
                                  statement.id,
                                  page: statement.entries.currentPage + 1,
                                ),
                          child: Text(tr('Next')),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
      );
    },
  );
  Widget _amount(
    BuildContext context,
    String title,
    int cents,
    String currency,
  ) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(walletText(context, title)),
        Text(
          walletAmount(currency, cents),
          style: Theme.of(context).textTheme.titleMedium,
        ),
      ],
    ),
  );
}

class _Controls extends StatefulWidget {
  const _Controls({
    required this.statement,
    required this.busy,
    required this.onSave,
    required this.onCancel,
    super.key,
  });
  final WalletStatement statement;
  final bool busy;
  final Future<void> Function(int, bool, List<String>) onSave;
  final VoidCallback onCancel;
  @override
  State<_Controls> createState() => _ControlsState();
}

class _ControlsState extends State<_Controls> {
  final form = GlobalKey<FormState>();
  late final TextEditingController amount, categories;
  late bool all;
  @override
  void initState() {
    super.initState();
    final value =
        widget.statement.limits.dailySpendCents ??
        widget.statement.schoolDailySpendCents;
    amount = TextEditingController(
      text: '${value ~/ 100}.${(value % 100).toString().padLeft(2, '0')}',
    );
    categories = TextEditingController(
      text: widget.statement.limits.categories.join(', '),
    );
    all = widget.statement.limits.allCategories;
  }

  @override
  void dispose() {
    amount.clear();
    categories.clear();
    amount.dispose();
    categories.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    String tr(String s) => walletText(context, s);
    return Form(
      key: form,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                tr('Spending controls'),
                style: Theme.of(context).textTheme.titleLarge,
              ),
              TextFormField(
                controller: amount,
                enabled: !widget.busy,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: InputDecoration(
                  labelText: tr('Daily spending limit'),
                  helperText: walletAmount(
                    widget.statement.currency,
                    widget.statement.schoolDailySpendCents,
                  ),
                ),
                validator: (s) {
                  final cents = walletCents(s ?? '');
                  return cents == null ||
                          cents > widget.statement.schoolDailySpendCents
                      ? tr('Enter an amount within the school daily cap.')
                      : null;
                },
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(tr('Allow all vendor categories')),
                value: all,
                onChanged: widget.busy
                    ? null
                    : (value) => setState(() => all = value),
              ),
              if (!all)
                TextFormField(
                  controller: categories,
                  enabled: !widget.busy,
                  decoration: InputDecoration(
                    labelText: tr('Allowed vendor categories'),
                    helperText: tr(
                      'Separate vendor category names with commas. Leave empty to block all categories.',
                    ),
                  ),
                  validator: (s) {
                    final list = _categories();
                    return list.length > 50 ||
                            list.any(
                              (c) => !RegExp(
                                r'^[a-z][a-z0-9_-]{0,39}$',
                              ).hasMatch(c),
                            )
                        ? tr('Use the category names shown by vendors.')
                        : null;
                  },
                ),
              Text(
                tr(
                  'A zero daily limit stops purchases. Transfers and withdrawal rules are separate.',
                ),
              ),
              Wrap(
                spacing: 12,
                children: [
                  FilledButton(
                    onPressed: widget.busy
                        ? null
                        : () {
                            if (form.currentState!.validate()) {
                              widget.onSave(
                                walletCents(amount.text)!,
                                all,
                                all ? [] : _categories(),
                              );
                            }
                          },
                    child: Text(tr('Save spending controls')),
                  ),
                  TextButton(
                    onPressed: widget.busy ? null : widget.onCancel,
                    child: Text(tr('Cancel')),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  List<String> _categories() =>
      categories.text
          .split(',')
          .map((s) => s.trim().toLowerCase().replaceAll(' ', '_'))
          .where((s) => s.isNotEmpty)
          .toSet()
          .toList()
        ..sort();
}

class _DeskReceipts extends StatelessWidget {
  const _DeskReceipts({
    required this.page,
    required this.busy,
    required this.onPage,
  });
  final WalletDeskReceiptPage page;
  final bool busy;
  final void Function(int) onPage;
  @override
  Widget build(BuildContext context) {
    String tr(String s) => walletText(context, s);
    final labels = MaterialLocalizations.of(context);
    String date(DateTime value) {
      final local = value.toLocal();
      return '${labels.formatMediumDate(local)} ${labels.formatTimeOfDay(TimeOfDay.fromDateTime(local))}';
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          tr('Desk top-up receipts'),
          style: Theme.of(context).textTheme.titleLarge,
        ),
        Text(
          tr(
            'School desk credits appear in your balance after required approval.',
          ),
        ),
        if (page.readOnly) Text(tr('Wallet history is read-only.')),
        if (page.items.isEmpty) Text(tr('No desk top-up receipts yet.')),
        for (final receipt in page.items)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    receipt.reference,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  Text(walletAmount(receipt.currency, receipt.amountCents)),
                  Text(
                    tr(switch (receipt.status) {
                      WalletDeskReceiptStatusEnum.pending =>
                        'Awaiting independent approval',
                      WalletDeskReceiptStatusEnum.released => 'Credited',
                      WalletDeskReceiptStatusEnum.cancelled => 'Cancelled',
                      _ => 'Status unavailable',
                    }),
                  ),
                  Text('${tr('Recorded on')}: ${date(receipt.createdAt)}'),
                  if (receipt.processedAt != null)
                    Text(
                      '${tr('Processed on')}: ${date(receipt.processedAt!)}',
                    ),
                ],
              ),
            ),
          ),
        Wrap(
          alignment: WrapAlignment.spaceBetween,
          spacing: 12,
          runSpacing: 8,
          children: [
            TextButton(
              onPressed: busy || page.currentPage <= 1
                  ? null
                  : () => onPage(page.currentPage - 1),
              child: Text(tr('Previous receipts')),
            ),
            TextButton(
              onPressed: busy || page.currentPage * page.perPage >= page.total
                  ? null
                  : () => onPage(page.currentPage + 1),
              child: Text(tr('Next receipts')),
            ),
          ],
        ),
      ],
    );
  }
}
