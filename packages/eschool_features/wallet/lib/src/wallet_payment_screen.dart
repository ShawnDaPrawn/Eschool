import 'dart:async';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:qr_flutter/qr_flutter.dart';

import 'wallet_payment_cubit.dart';
import 'wallet_payment_repository.dart';
import 'wallet_text.dart';

class WalletPaymentScreen extends StatelessWidget {
  const WalletPaymentScreen({required this.repository, this.quote, super.key});
  final WalletPaymentRepository repository;
  final CommerceQuote? quote;
  @override
  Widget build(BuildContext context) => BlocProvider(
    create: (_) => WalletPaymentCubit(repository)..load(quote: quote),
    child: const _PaymentView(),
  );
}

class _PaymentView extends StatefulWidget {
  const _PaymentView();
  @override
  State<_PaymentView> createState() => _PaymentViewState();
}

class _PaymentViewState extends State<_PaymentView>
    with WidgetsBindingObserver {
  Timer? _expiry;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    _expiry?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _expiry?.cancel();
    final cubit = context.read<WalletPaymentCubit>();
    if (state == AppLifecycleState.resumed) {
      cubit.load();
    } else {
      cubit.suspend();
    }
  }

  @override
  Widget build(
    BuildContext context,
  ) => BlocConsumer<WalletPaymentCubit, WalletPaymentState>(
    listener: (context, state) {
      _expiry?.cancel();
      final row = state.payment;
      if (row?.qr != null && row!.expiresAt.isAfter(DateTime.now().toUtc())) {
        final remaining = row.expiresAt.difference(DateTime.now().toUtc());
        _expiry = Timer(remaining.isNegative ? Duration.zero : remaining, () {
          if (mounted) context.read<WalletPaymentCubit>().read(row.id);
        });
      }
    },
    builder: (context, state) {
      final cubit = context.read<WalletPaymentCubit>();
      String tr(String s) => walletText(context, s);
      final row = state.payment;
      final quote = state.quote;
      final validQr =
          row?.qr != null && row!.expiresAt.isAfter(DateTime.now().toUtc());
      return ScreenScaffold(
        title: tr('Wallet payments'),
        body: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (state.busy) const LinearProgressIndicator(),
            if (state.accessLost)
              Text(tr('Your wallet access changed. Reopen Wallets.')),
            if (state.error != null)
              Text(tr(state.error!), semanticsLabel: tr(state.error!)),
            if (cubit.hasPending) ...[
              Text(
                tr(
                  'Check the original wallet request before creating another payment QR.',
                ),
              ),
              FilledButton(
                onPressed: state.busy ? null : cubit.recover,
                child: Text(tr('Check wallet request outcomes')),
              ),
            ],
            if (quote != null) ...[
              Text(
                quote.seller.legalName,
                style: Theme.of(context).textTheme.titleLarge,
              ),
              for (final line in quote.lines)
                Text(
                  '${line.productName} · ${line.quantity} · ${walletAmount(quote.currency, line.grossCents)}',
                ),
              Text(
                '${tr('Total from original wallet')}: ${walletAmount(quote.currency, quote.amountCents)}',
              ),
              Text(
                tr(
                  'Creating a QR moves no money. Show it only at this Vendor counter. Confirming at the counter pays once and hands over these goods.',
                ),
              ),
              FilledButton(
                onPressed: state.busy || cubit.hasPending ? null : cubit.issue,
                child: Text(tr('Create payment QR for these items')),
              ),
            ],
            if (row != null) ...[
              Text(
                row.seller.legalName,
                style: Theme.of(context).textTheme.titleLarge,
              ),
              Text('${tr('Original wallet')}: ${row.walletOwnerName}'),
              for (final line in row.lines)
                Text(
                  '${line.productName} · ${line.quantity} · ${walletAmount(row.currency, line.grossCents)}',
                ),
              Text(
                '${tr('Total from original wallet')}: ${walletAmount(row.currency, row.amountCents)}',
              ),
              Text(
                '${tr('Payment status')}: ${tr(switch (row.status) {
                  WalletPaymentPersonalStatusEnum.awaitingCounter => 'Awaiting counter confirmation',
                  WalletPaymentPersonalStatusEnum.paid => 'Paid',
                  WalletPaymentPersonalStatusEnum.cancelled => 'Cancelled',
                  _ => 'Expired',
                })}',
              ),
              if (validQr) ...[
                Center(
                  child: Semantics(
                    label: tr('Private wallet payment QR'),
                    child: QrImageView(data: row.qr!, size: 220),
                  ),
                ),
                Text(
                  tr(
                    'This payment QR expires shortly. Keep it private and check the original receipt after the counter confirms.',
                  ),
                ),
                TextButton(
                  onPressed: state.busy ? null : cubit.cancel,
                  child: Text(tr('Cancel this unpaid payment QR')),
                ),
              ],
              if (row.orderId != null)
                Text('${tr('Original order receipt')}: ${row.orderId}'),
              FilledButton(
                onPressed: state.busy ? null : () => cubit.read(row.id),
                child: Text(tr('Check this payment outcome')),
              ),
            ],
            for (final item in state.page?.items ?? <WalletPaymentPersonal>[])
              ListTile(
                title: Text(item.seller.legalName),
                subtitle: Text(walletAmount(item.currency, item.amountCents)),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => cubit.read(item.id),
              ),
            if ((state.page?.page ?? 1) > 1)
              TextButton(
                onPressed: () => cubit.load(page: state.page!.page - 1),
                child: Text(tr('Previous payments')),
              ),
            if ((state.page?.page ?? 1) < (state.page?.lastPage ?? 1))
              TextButton(
                onPressed: () => cubit.load(page: state.page!.page + 1),
                child: Text(tr('More payments')),
              ),
            TextButton(
              onPressed: state.busy ? null : () => cubit.load(),
              child: Text(tr('Refresh wallet payments')),
            ),
          ],
        ),
      );
    },
  );
}
