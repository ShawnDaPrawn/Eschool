import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'fee_statement_cubit.dart';
import 'fee_statement_repository.dart';
import 'fee_text.dart';

class FeeStatementScreen extends StatefulWidget {
  const FeeStatementScreen({
    required this.repository,
    this.learnerNames = const {},
    super.key,
  });
  final FeeStatementRepository repository;
  final Map<int, String> learnerNames;

  @override
  State<FeeStatementScreen> createState() => _FeeStatementScreenState();
}

class _FeeStatementScreenState extends State<FeeStatementScreen>
    with WidgetsBindingObserver {
  late FeeStatementCubit _cubit;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _cubit = FeeStatementCubit(widget.repository)..accounts();
  }

  @override
  void didUpdateWidget(FeeStatementScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!identical(widget.repository, oldWidget.repository)) {
      _cubit.close();
      _cubit = FeeStatementCubit(widget.repository)..accounts();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _cubit.accounts();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _cubit.close();
    super.dispose();
  }

  Widget _text(String label, {TextStyle? style}) =>
      Text(feeText(context, label), style: style);

  Widget _amount(String label, int cents, String currency) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4),
    child: Wrap(
      spacing: 12,
      runSpacing: 4,
      alignment: WrapAlignment.spaceBetween,
      children: [
        _text(label),
        Text(
          feeAmount(currency, cents),
          style: Theme.of(context).textTheme.titleMedium,
        ),
      ],
    ),
  );

  Widget _scope() => Padding(
    padding: const EdgeInsets.only(bottom: 16),
    child: _text(
      'Only your shares and receipts for learners you can currently view are shown. Earlier fee history is separate.',
    ),
  );

  Widget _pager({
    required String label,
    required int page,
    required int perPage,
    required int total,
    required void Function(int page) change,
  }) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: Wrap(
      spacing: 12,
      runSpacing: 8,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: [
        Text('${feeText(context, label)}: $page'),
        if (page > 1)
          OutlinedButton(
            onPressed: () => change(page - 1),
            child: _text('Previous page'),
          ),
        if (page * perPage < total)
          OutlinedButton(
            onPressed: () => change(page + 1),
            child: _text('Next page'),
          ),
      ],
    ),
  );

  Widget _accounts(ParentFeeAccountsPage page) {
    if (page.items.isEmpty) {
      return EmptyStateMessage(
        icon: Icons.receipt_long_outlined,
        title: feeText(context, 'No fee account to show yet'),
        message: feeText(
          context,
          'Your school has not posted a fee account you can currently view.',
        ),
        action: page.currentPage > 1
            ? OutlinedButton(
                onPressed: () => _cubit.accounts(),
                child: _text('Refresh fee statements'),
              )
            : null,
      );
    }
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _scope(),
        for (final account in page.items)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Semantics(
                    header: true,
                    child: Text(
                      account.name,
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                  ),
                  _amount(
                    'Your invoice balance still due',
                    account.outstandingCents,
                    account.currency,
                  ),
                  _amount(
                    'Your held credit',
                    account.creditCents,
                    account.currency,
                  ),
                  if (account.nextDueOn != null)
                    Text(
                      '${feeText(context, 'Next due date')}: ${feeDate(account.nextDueOn!)}',
                    ),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: () => _cubit.statement(account.id),
                    child: _text('Open statement'),
                  ),
                ],
              ),
            ),
          ),
        _pager(
          label: 'Account page',
          page: page.currentPage,
          perPage: page.perPage,
          total: page.total,
          change: (p) => _cubit.accounts(page: p),
        ),
      ],
    );
  }

  Widget _statement(ParentFeeStatement statement) => ListView(
    padding: const EdgeInsets.all(16),
    children: [
      _scope(),
      Text(statement.name, style: Theme.of(context).textTheme.titleLarge),
      if (statement.grossChargedCents != null ||
          statement.creditedCents != null) ...[
        _amount(
          'Your original invoice shares',
          statement.grossChargedCents ?? statement.chargedCents,
          statement.currency,
        ),
        _amount(
          'Credits against your shares',
          statement.creditedCents ?? 0,
          statement.currency,
        ),
      ],
      _amount('Your net charges', statement.chargedCents, statement.currency),
      _amount(
        'Payments applied to your shares',
        statement.allocatedCents,
        statement.currency,
      ),
      _amount(
        'Your invoice balance still due',
        statement.outstandingCents,
        statement.currency,
      ),
      _amount('Your held credit', statement.creditCents, statement.currency),
      _text(
        'Held or learner-reserved credit reduces a bill only after allocation.',
      ),
      const SizedBox(height: 20),
      Semantics(
        header: true,
        child: _text(
          'Your learner charges',
          style: Theme.of(context).textTheme.titleLarge,
        ),
      ),
      if (statement.charges.items.isEmpty)
        _text('No posted charges on this page.'),
      for (final charge in statement.charges.items)
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  charge.learnerName,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                Text(charge.description),
                if (charge.chargeKind?.value == 'fee_item')
                  _text('Additional fee'),
                if (charge.chargeScope?.value == 'family')
                  _text('Family charge apportioned to this learner'),
                Text(
                  '${charge.invoiceNumber} · ${feeDate(charge.periodStart)} – ${feeDate(charge.periodEnd)}',
                ),
                Text('${feeText(context, 'Due on')}: ${feeDate(charge.dueOn)}'),
                if (charge.grossChargedCents != null ||
                    charge.creditedCents != null) ...[
                  _amount(
                    'Original share',
                    charge.grossChargedCents ?? charge.chargedCents,
                    charge.currency,
                  ),
                  _amount(
                    'Credited',
                    charge.creditedCents ?? 0,
                    charge.currency,
                  ),
                ],
                _amount('Your net share', charge.chargedCents, charge.currency),
                if (charge.originalVatCents != null &&
                    charge.vatCreditedCents != null &&
                    charge.netChargedCents != null &&
                    charge.originalVatCents! > 0) ...[
                  _amount(
                    'VAT included in your charge',
                    charge.originalVatCents! - charge.vatCreditedCents!,
                    charge.currency,
                  ),
                  _amount(
                    'Your charge before VAT',
                    charge.netChargedCents!,
                    charge.currency,
                  ),
                ],
                _amount(
                  'Applied payments',
                  charge.allocatedCents,
                  charge.currency,
                ),
                _amount('Still due', charge.outstandingCents, charge.currency),
              ],
            ),
          ),
        ),
      _pager(
        label: 'Charges page',
        page: statement.charges.currentPage,
        perPage: statement.charges.perPage,
        total: statement.charges.total,
        change: (p) => _cubit.statement(
          statement.id,
          chargesPage: p,
          receiptsPage: statement.receipts.currentPage,
          creditsPage: statement.creditNotes?.currentPage ?? 1,
        ),
      ),
      const SizedBox(height: 20),
      Semantics(
        header: true,
        child: _text(
          'Your receipts and credits',
          style: Theme.of(context).textTheme.titleLarge,
        ),
      ),
      if (statement.receipts.items.isEmpty)
        _text('No permitted receipts on this page.'),
      for (final receipt in statement.receipts.items)
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  receipt.number,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                Text(
                  '${feeText(context, switch (receipt.method) {
                    ParentFeeReceiptMethodEnum.cash => 'Cash',
                    ParentFeeReceiptMethodEnum.eft => 'EFT',
                    _ => 'Payment',
                  })} · ${feeDate(receipt.receivedOn)}',
                ),
                if (receipt.pinnedLearnerId != null)
                  Text(
                    '${feeText(context, 'Reserved for learner')}: ${widget.learnerNames[receipt.pinnedLearnerId] ?? feeText(context, 'One learner')}',
                  ),
                _amount(
                  'Received toward your fees',
                  receipt.amountCents,
                  receipt.currency,
                ),
                _amount(
                  'Applied payments',
                  receipt.allocatedCents,
                  receipt.currency,
                ),
                _amount('Held credit', receipt.creditCents, receipt.currency),
                if ((receipt.releasedCents ?? 0) > 0)
                  _amount(
                    'Restored by credit notes',
                    receipt.releasedCents!,
                    receipt.currency,
                  ),
              ],
            ),
          ),
        ),
      _pager(
        label: 'Receipts page',
        page: statement.receipts.currentPage,
        perPage: statement.receipts.perPage,
        total: statement.receipts.total,
        change: (p) => _cubit.statement(
          statement.id,
          chargesPage: statement.charges.currentPage,
          receiptsPage: p,
          creditsPage: statement.creditNotes?.currentPage ?? 1,
        ),
      ),
      _credits(statement),
    ],
  );

  Widget _credits(ParentFeeStatement statement) {
    final notes = statement.creditNotes;
    if (notes == null) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const SizedBox(height: 20),
        Semantics(
          header: true,
          child: _text(
            'Your credit notes',
            style: Theme.of(context).textTheme.titleLarge,
          ),
        ),
        _text(
          'Credit notes correct charges; restored amounts stay in the original receipts.',
        ),
        if (notes.items.isEmpty) _text('No credit notes on this page.'),
        for (final note in notes.items)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    note.number,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  Text(note.learnerName),
                  Text('${note.invoiceNumber} · ${feeDate(note.issuedOn)}'),
                  _text(switch (note.reasonCode.value) {
                    'billing_error' => 'Billing correction',
                    'fee_reduction' => 'Fee reduction',
                    'leaver_credit' => 'Leaver credit',
                    _ => 'Other fee correction',
                  }),
                  _amount('Credit amount', note.amountCents, note.currency),
                  if ((note.vatCents ?? 0) > 0) ...[
                    _amount(
                      'VAT included in this credit',
                      note.vatCents!,
                      note.currency,
                    ),
                    _amount(
                      'Credit before VAT',
                      note.netCents ?? note.amountCents - note.vatCents!,
                      note.currency,
                    ),
                  ],
                  _amount('Debt reduction', note.unpaidCents, note.currency),
                  _amount(
                    'Restored to receipts',
                    note.releasedCents,
                    note.currency,
                  ),
                ],
              ),
            ),
          ),
        _pager(
          label: 'Credit notes page',
          page: notes.currentPage,
          perPage: notes.perPage,
          total: notes.total,
          change: (p) => _cubit.statement(
            statement.id,
            chargesPage: statement.charges.currentPage,
            receiptsPage: statement.receipts.currentPage,
            creditsPage: p,
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) =>
      BlocBuilder<FeeStatementCubit, FeeStatementState>(
        bloc: _cubit,
        builder: (context, state) => PopScope(
          canPop: state is! FeeStatementReady,
          onPopInvokedWithResult: (didPop, result) {
            if (!didPop && state is FeeStatementReady) _cubit.accounts();
          },
          child: ScreenScaffold(
            title: feeText(context, 'Fee statements'),
            onBack: state is FeeStatementReady ? () => _cubit.accounts() : null,
            action: state is FeeStatementLoading
                ? null
                : IconButton(
                    tooltip: feeText(context, 'Refresh fee statements'),
                    onPressed: () => _cubit.accounts(),
                    icon: const Icon(Icons.refresh),
                  ),
            body: ContentColumn(
              child: switch (state) {
                FeeStatementLoading() => const Center(
                  child: CircularProgressIndicator(),
                ),
                FeeAccountsReady() => _accounts(state.page),
                FeeStatementReady() => _statement(state.statement),
                FeeStatementFailure() => EmptyStateMessage(
                  icon: Icons.receipt_long_outlined,
                  title: feeText(
                    context,
                    state.accessChanged
                        ? 'Your fee access changed'
                        : 'Current fee statement unavailable',
                  ),
                  message: feeText(
                    context,
                    state.accessChanged
                        ? 'Reopen this screen after refreshing your account.'
                        : 'Reconnect and try again to view current balances.',
                  ),
                  action: state.accessChanged
                      ? null
                      : OutlinedButton(
                          onPressed: () => _cubit.accounts(),
                          child: _text('Try again'),
                        ),
                ),
              },
            ),
          ),
        ),
      );
}
