import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'fee_opt_in_cubit.dart';
import 'fee_opt_in_repository.dart';
import 'fee_text.dart';

class FeeOptInScreen extends StatefulWidget {
  const FeeOptInScreen({
    required this.repository,
    required this.familyId,
    required this.onBack,
    super.key,
  });
  final FeeOptInRepository repository;
  final int familyId;
  final VoidCallback onBack;
  @override
  State<FeeOptInScreen> createState() => _FeeOptInScreenState();
}

class _FeeOptInScreenState extends State<FeeOptInScreen>
    with WidgetsBindingObserver {
  late FeeOptInCubit _cubit;
  bool _confirmed = false;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _cubit = FeeOptInCubit(widget.repository, widget.familyId)..load();
  }

  @override
  void didUpdateWidget(FeeOptInScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!identical(oldWidget.repository, widget.repository) ||
        oldWidget.familyId != widget.familyId) {
      _cubit.close();
      _confirmed = false;
      _cubit = FeeOptInCubit(widget.repository, widget.familyId)..load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _cubit.load();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _cubit.close();
    super.dispose();
  }

  Widget _text(String text) => Text(feeText(context, text));
  Widget _amount(String label, int cents, String currency) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4),
    child: Wrap(
      spacing: 12,
      runSpacing: 4,
      children: [_text(label), Text(feeAmount(currency, cents))],
    ),
  );
  String _frequency(ParentFeeOptionalItem item) => switch (item.billing.value) {
    'once_per_year' => 'Once per school year',
    'per_occurrence' => 'Per billed occurrence until withdrawn',
    _ => 'This billing option needs an app update',
  };
  Widget _terms(ParentFeeOptionalItem item) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(item.name, style: Theme.of(context).textTheme.titleMedium),
      Text(item.learnerName ?? feeText(context, 'Family')),
      _text(_frequency(item)),
      if (item.available &&
          item.priceCents != null &&
          item.shareCents != null) ...[
        _amount('Published item price', item.priceCents!, item.currency),
        _amount(
          'Your share for each billed occurrence',
          item.shareCents!,
          item.currency,
        ),
        if ((item.vatCents ?? 0) > 0)
          _amount('VAT included in your share', item.vatCents!, item.currency),
      ] else
        _text(
          'The current price is unavailable. You can withdraw a previous approval.',
        ),
    ],
  );
  void _review(ParentFeeOptionalItem item, bool active) {
    setState(() => _confirmed = false);
    _cubit.review(item, active: active);
  }

  Widget _ready(FeeOptInReady state) => ListView(
    padding: const EdgeInsets.all(16),
    children: [
      _text(
        'Only your approvals and your shares for learners you can currently view are shown.',
      ),
      const SizedBox(height: 12),
      _text(
        'Approving does not make a payment. The school bills the item under the terms shown, after every current payer has approved.',
      ),
      if (state.message != null)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: Semantics(liveRegion: true, child: _text(state.message!)),
        ),
      if (!state.page.writable)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: _text(
            'Optional fee items are read-only while fees are disabled.',
          ),
        ),
      if (state.page.items.isEmpty)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 24),
          child: _text(
            'No optional fee items are currently available for this account.',
          ),
        ),
      for (final item in state.page.items)
        Card(
          key: ValueKey('optional-${item.feeItemId}-${item.learnerId}'),
          margin: const EdgeInsets.symmetric(vertical: 8),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _terms(item),
                const SizedBox(height: 8),
                _text(
                  item.currentTermsApproved
                      ? 'Your current approval is recorded.'
                      : item.selected
                      ? 'The terms changed. Review before any further optional charges.'
                      : 'You have not approved this item.',
                ),
                if (!item.canApprove)
                  _text(
                    'Your guardian permissions allow viewing but not approving this item.',
                  ),
                if (item.currentTermsApproved)
                  _text(
                    'Every current payer must approve before the school can bill.',
                  ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 12,
                  runSpacing: 8,
                  children: [
                    if (!item.currentTermsApproved)
                      FilledButton(
                        onPressed:
                            state.page.writable &&
                                item.canApprove &&
                                item.available &&
                                supportedOptionalFee(item)
                            ? () => _review(item, true)
                            : null,
                        child: _text(
                          item.selected
                              ? 'Review new terms'
                              : 'Review optional fee',
                        ),
                      ),
                    if (item.selected)
                      OutlinedButton(
                        onPressed: state.page.writable && item.canApprove
                            ? () => _review(item, false)
                            : null,
                        child: _text('Withdraw approval'),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ),
      Wrap(
        spacing: 12,
        runSpacing: 8,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          Text(
            '${feeText(context, 'Optional item page')}: ${state.page.currentPage}',
          ),
          if (state.page.currentPage > 1)
            OutlinedButton(
              onPressed: () => _cubit.load(page: state.page.currentPage - 1),
              child: _text('Previous page'),
            ),
          if (state.page.currentPage * state.page.perPage < state.page.total)
            OutlinedButton(
              onPressed: () => _cubit.load(page: state.page.currentPage + 1),
              child: _text('Next page'),
            ),
        ],
      ),
    ],
  );
  Widget _reviewing(FeeOptInReview state) => ListView(
    padding: const EdgeInsets.all(16),
    children: [
      _text(
        state.active
            ? 'Review your optional fee approval'
            : 'Review withdrawal of your approval',
      ),
      const SizedBox(height: 16),
      _terms(state.offer),
      const SizedBox(height: 16),
      _text(
        state.active
            ? 'Your approval applies to the current school year and the billing frequency shown. A changed price or payer allocation needs a new approval.'
            : 'Withdrawing stops future optional charges that require your approval. It does not cancel invoices already posted or make a refund.',
      ),
      if (state.retry)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: Semantics(
            liveRegion: true,
            child: _text(
              'The result could not be confirmed. Retry this same approval change safely.',
            ),
          ),
        ),
      Material(
        color: Colors.transparent,
        child: CheckboxListTile(
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          value: _confirmed,
          onChanged: state.busy
              ? null
              : (value) => setState(() => _confirmed = value ?? false),
          title: _text(
            state.active
                ? 'I approve my share and the billing frequency shown.'
                : 'I confirm withdrawal of my approval for future charges.',
          ),
        ),
      ),
      const SizedBox(height: 12),
      Wrap(
        spacing: 12,
        runSpacing: 8,
        children: [
          FilledButton(
            onPressed: !state.busy && _confirmed
                ? () => _cubit.confirm()
                : null,
            child: _text(
              state.retry
                  ? 'Retry same approval change'
                  : state.active
                  ? 'Record my approval'
                  : 'Confirm withdrawal',
            ),
          ),
          OutlinedButton(
            onPressed: state.busy ? null : () => _cubit.cancelReview(),
            child: _text('Cancel review'),
          ),
        ],
      ),
      if (state.busy)
        const Padding(
          padding: EdgeInsets.all(16),
          child: Center(child: CircularProgressIndicator()),
        ),
    ],
  );
  Widget _failure(bool denied) => EmptyStateMessage(
    icon: Icons.receipt_long_outlined,
    title: feeText(
      context,
      denied ? 'Your fee access changed' : 'Current optional fees unavailable',
    ),
    message: feeText(
      context,
      denied
          ? 'Reopen this screen after refreshing your account.'
          : 'Reconnect and try again to view current offers.',
    ),
    action: denied
        ? null
        : FilledButton(
            onPressed: () => _cubit.load(),
            child: _text('Try again'),
          ),
  );
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<FeeOptInCubit, FeeOptInState>(
        bloc: _cubit,
        builder: (context, state) => PopScope(
          canPop: false,
          onPopInvokedWithResult: (didPop, result) {
            if (!didPop) widget.onBack();
          },
          child: ScreenScaffold(
            title: feeText(context, 'Optional fee items'),
            onBack: widget.onBack,
            action:
                state is FeeOptInLoading ||
                    (state is FeeOptInReview && state.busy)
                ? null
                : IconButton(
                    tooltip: feeText(context, 'Refresh optional fee items'),
                    onPressed: () => _cubit.load(),
                    icon: const Icon(Icons.refresh),
                  ),
            body: ContentColumn(
              child: !widget.repository.isActive
                  ? _failure(true)
                  : switch (state) {
                      FeeOptInLoading() => const Center(
                        child: CircularProgressIndicator(),
                      ),
                      FeeOptInReady() => _ready(state),
                      FeeOptInReview() => _reviewing(state),
                      FeeOptInFailure() => _failure(state.accessChanged),
                    },
            ),
          ),
        ),
      );
}
