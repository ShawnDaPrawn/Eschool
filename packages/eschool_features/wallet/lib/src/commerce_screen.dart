import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:qr_flutter/qr_flutter.dart';

import 'commerce_cubit.dart';
import 'commerce_repository.dart';
import 'wallet_text.dart';

class CommerceScreen extends StatelessWidget {
  const CommerceScreen({required this.repository, super.key});
  final CommerceRepository repository;
  @override
  Widget build(BuildContext context) => BlocProvider(
    create: (_) => CommerceCubit(repository)..load(),
    child: const _CommerceView(),
  );
}

class _CommerceView extends StatefulWidget {
  const _CommerceView();
  @override
  State<_CommerceView> createState() => _CommerceViewState();
}

class _CommerceViewState extends State<_CommerceView> {
  final Map<int, int> _cart = {};
  int? _wallet;
  bool _orders = false, _confirmed = false;
  void _clear() {
    _cart.clear();
    _wallet = null;

    _confirmed = false;
  }

  @override
  Widget build(
    BuildContext context,
  ) => BlocConsumer<CommerceCubit, CommerceState>(
    listener: (_, state) {
      if (state.accessLost) _clear();
    },
    builder: (context, state) {
      final cubit = context.read<CommerceCubit>();
      String tr(String s) => walletText(context, s);
      final choices = [
        for (final owner in state.wallets?.owners ?? <WalletOwner>[])
          if (owner.canOpen)
            ...owner.wallets.where(
              (w) => !w.readOnly && w.limits.effectiveDailySpendCents > 0,
            ),
      ];
      final selected = choices.any((w) => w.id == _wallet)
          ? _wallet
          : choices.firstOrNull?.id;
      return ScreenScaffold(
        title: tr('Shop and orders'),
        action: IconButton(
          tooltip: tr('Refresh shop and orders'),
          onPressed: state.busy
              ? null
              : () {
                  _clear();
                  cubit.load();
                },
          icon: const Icon(Icons.refresh),
        ),
        body: ListView(
          key: const ValueKey('commerce-screen-scroll'),
          padding: const EdgeInsets.all(16),
          children: [
            if (state.busy)
              LinearProgressIndicator(
                semanticsLabel: tr('Loading shop and orders'),
              ),
            if (cubit.hasPendingMoney)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        tr(
                          'A wallet request needs an outcome check before another purchase.',
                        ),
                      ),
                      FilledButton(
                        onPressed: state.busy
                            ? null
                            : () {
                                _clear();
                                cubit.recover();
                              },
                        child: Text(tr('Check wallet request outcomes')),
                      ),
                    ],
                  ),
                ),
              ),
            if (state.accessLost)
              Text(tr('Your storefront access changed. Reopen Wallets.')),
            if (state.error != null)
              Text(tr(state.error!), semanticsLabel: tr(state.error!)),
            if (state.message != null)
              Semantics(liveRegion: true, child: Text(tr(state.message!))),
            if (state.wallets != null) ...[
              Wrap(
                spacing: 12,
                children: [
                  TextButton(
                    onPressed: () {
                      setState(() {
                        _orders = false;
                        _clear();
                      });
                      cubit.load();
                    },
                    child: Text(tr('Shop')),
                  ),
                  TextButton(
                    onPressed: () {
                      setState(() {
                        _orders = true;
                        _clear();
                      });
                      cubit.load();
                    },
                    child: Text(tr('Your orders')),
                  ),
                ],
              ),
              if (_orders) ...[
                Text(
                  tr('Your original order receipts'),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                if (state.orders?.items.isEmpty ?? true)
                  Text(tr('No order receipts yet.')),
                for (final order
                    in state.orders?.items ?? <CommerceOrderReceipt>[])
                  _OrderCard(
                    key: ValueKey(order.id),
                    order: order,
                    enabled: !cubit.hasPendingMoney,
                  ),
                if ((state.orders?.page ?? 1) > 1)
                  TextButton(
                    onPressed: () =>
                        cubit.load(orderPage: state.orders!.page - 1),
                    child: Text(tr('Previous orders')),
                  ),
                if ((state.orders?.page ?? 1) < (state.orders?.lastPage ?? 1))
                  TextButton(
                    onPressed: () =>
                        cubit.load(orderPage: state.orders!.page + 1),
                    child: Text(tr('More orders')),
                  ),
              ] else if (state.quote != null) ...[
                Text(
                  tr('Review quoted purchase'),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                Text(state.quote!.seller.legalName),
                Text(
                  '${tr('Original wallet')}: ${state.wallets!.owners.expand((o) => o.wallets).where((w) => w.id == state.quote!.walletId).first.ownerName}',
                ),
                for (final line in state.quote!.lines)
                  Text(
                    '${line.productName} · ${line.quantity} · ${walletAmount(state.quote!.currency, line.grossCents)}',
                  ),
                Text(
                  '${tr('VAT included')}: ${walletAmount(state.quote!.currency, state.quote!.vatCents)}',
                ),
                Text(
                  '${tr('Total from original wallet')}: ${walletAmount(state.quote!.currency, state.quote!.amountCents)}',
                ),
                Text(
                  tr(
                    'Prices and availability are checked again when you confirm. An expired quote needs a new review.',
                  ),
                ),
                CheckboxListTile(
                  value: _confirmed,
                  onChanged: cubit.hasPendingMoney
                      ? null
                      : (v) => setState(() => _confirmed = v == true),
                  title: Text(
                    tr(
                      'I reviewed the original wallet, items, quantities and total. Purchase once.',
                    ),
                  ),
                ),
                FilledButton(
                  onPressed: !_confirmed || cubit.hasPendingMoney
                      ? null
                      : () async {
                          if (await cubit.purchase() && mounted) {
                            setState(() {
                              _orders = true;
                              _clear();
                            });
                          }
                        },
                  child: Text(tr('Confirm quoted purchase')),
                ),
                TextButton(
                  onPressed: () {
                    _confirmed = false;
                    cubit.openVendor(state.quote!.vendorId);
                  },
                  child: Text(tr('Change items and review again')),
                ),
              ] else if (state.catalogue != null) ...[
                Text(
                  state.catalogue!.vendor.name,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                if (state.vendors?.canPurchase != true ||
                    !state.catalogue!.canPurchase)
                  Text(
                    tr(
                      'Purchases are paused. Existing receipts remain available.',
                    ),
                  ),
                if (choices.isEmpty)
                  Text(
                    tr(
                      'No wallet can currently make purchases. Review wallet controls or ask Finance.',
                    ),
                  ),
                if (choices.isNotEmpty)
                  DropdownButtonFormField<int>(
                    key: ValueKey('commerce-wallet-$selected'),
                    initialValue: selected,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: tr('Pay from wallet'),
                    ),
                    items: [
                      for (final wallet in choices)
                        DropdownMenuItem(
                          value: wallet.id,
                          child: Text(
                            '${wallet.ownerName} · ${walletAmount(wallet.currency, wallet.balanceCents)}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                    ],
                    onChanged: cubit.hasPendingMoney
                        ? null
                        : (v) => setState(() => _wallet = v),
                  ),
                if (state.catalogue!.items.isEmpty)
                  Text(tr('No products are currently available.')),
                for (final product in state.catalogue!.items)
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            product.name,
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                          if (product.description?.isNotEmpty ?? false)
                            Text(product.description!),
                          Text(
                            '${walletAmount(product.currency, product.priceCents)} · ${product.unitLabel}',
                          ),
                          if (!product.inStock)
                            Text(
                              tr(
                                'Currently unavailable; remove it from your cart.',
                              ),
                            ),
                          TextFormField(
                            key: ValueKey('commerce-quantity-${product.id}'),
                            initialValue: '${_cart[product.id] ?? 0}',
                            keyboardType: TextInputType.number,
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                            ],
                            maxLength: 3,
                            enabled:
                                !cubit.hasPendingMoney &&
                                state.catalogue!.canPurchase,
                            decoration: InputDecoration(
                              labelText: '${tr('Quantity')} · ${product.name}',
                            ),
                            onChanged: (value) {
                              final quantity = int.tryParse(value) ?? 0;
                              setState(() {
                                if (quantity == 0) {
                                  _cart.remove(product.id);
                                } else {
                                  _cart[product.id] = quantity;
                                }
                              });
                            },
                          ),
                        ],
                      ),
                    ),
                  ),
                if (_cart.length > 20)
                  Text(tr('Choose up to 20 different products.')),
                FilledButton(
                  onPressed:
                      selected == null ||
                          _cart.isEmpty ||
                          _cart.length > 20 ||
                          cubit.hasPendingMoney ||
                          state.vendors?.canPurchase != true ||
                          !state.catalogue!.canPurchase
                      ? null
                      : () {
                          _wallet = selected;
                          _confirmed = false;
                          cubit.review(
                            state.catalogue!.vendor.id,
                            selected,
                            _cart,
                          );
                        },
                  child: Text(tr('Review cart')),
                ),
                if (state.catalogue!.page > 1)
                  TextButton(
                    onPressed: () => cubit.openVendor(
                      state.catalogue!.vendor.id,
                      page: state.catalogue!.page - 1,
                    ),
                    child: Text(tr('Previous products')),
                  ),
                if (state.catalogue!.page < state.catalogue!.lastPage)
                  TextButton(
                    onPressed: () => cubit.openVendor(
                      state.catalogue!.vendor.id,
                      page: state.catalogue!.page + 1,
                    ),
                    child: Text(tr('More products')),
                  ),
                TextButton(
                  onPressed: () {
                    _clear();
                    cubit.load();
                  },
                  child: Text(tr('Back to vendors')),
                ),
              ] else ...[
                Text(
                  tr('School vendors'),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                if (state.vendors?.items.isEmpty ?? true)
                  Text(tr('No vendors are currently available.')),
                for (final vendor
                    in state.vendors?.items ?? <CommerceVendorBrief>[])
                  Card(
                    child: ListTile(
                      title: Text(vendor.name),
                      subtitle: vendor.description == null
                          ? null
                          : Text(vendor.description!),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: () {
                        _clear();

                        cubit.openVendor(vendor.id);
                      },
                    ),
                  ),
                if ((state.vendors?.page ?? 1) > 1)
                  TextButton(
                    onPressed: () =>
                        cubit.load(vendorPage: state.vendors!.page - 1),
                    child: Text(tr('Previous vendors')),
                  ),
                if ((state.vendors?.page ?? 1) < (state.vendors?.lastPage ?? 1))
                  TextButton(
                    onPressed: () =>
                        cubit.load(vendorPage: state.vendors!.page + 1),
                    child: Text(tr('More vendors')),
                  ),
              ],
            ],
          ],
        ),
      );
    },
  );
}

class _OrderCard extends StatefulWidget {
  const _OrderCard({required this.order, required this.enabled, super.key});
  final CommerceOrderReceipt order;
  final bool enabled;
  @override
  State<_OrderCard> createState() => _OrderCardState();
}

class _OrderCardState extends State<_OrderCard> {
  final _reason = TextEditingController();
  bool _confirmed = false;
  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    String tr(String s) => walletText(context, s);
    final order = widget.order;
    final status = switch (order.status) {
      CommerceOrderReceiptStatusEnum.ready => 'Ready for collection',
      CommerceOrderReceiptStatusEnum.collected => 'Collected',
      CommerceOrderReceiptStatusEnum.cancelled => 'Cancelled',
      CommerceOrderReceiptStatusEnum.unknownDefaultOpenApi =>
        'Refresh order status',
    };
    return Card(
      child: ExpansionTile(
        title: Text(order.orderNumber),
        subtitle: Text(
          '${tr(status)} · ${walletAmount(order.currency, order.amountCents)}',
        ),
        childrenPadding: const EdgeInsets.all(16),
        children: [
          Text(order.seller.legalName),
          if (order.seller.legalAddress != null)
            Text(order.seller.legalAddress!),
          if (order.seller.vatRegistered)
            Text('${tr('VAT registration')}: ${order.seller.vatNumber}'),
          for (final line in order.lines)
            Text(
              '${line.name} · ${line.quantity} · ${walletAmount(order.currency, line.grossCents)}',
            ),
          Text(
            '${tr('VAT included')}: ${walletAmount(order.currency, order.vatCents)}',
          ),
          if (order.qr != null) ...[
            Text(tr('Show this QR or the order number at the vendor counter.')),
            ColoredBox(
              color: Colors.white,
              child: QrImageView(
                key: ValueKey('order-collection-qr-${order.id}'),
                data: order.qr!,
                size: 200,
                semanticsLabel: tr('Order collection QR'),
              ),
            ),
          ],
          if (order.canCancel && widget.enabled) ...[
            TextField(
              controller: _reason,
              onChanged: (_) => setState(() {}),
              maxLength: 2000,
              maxLines: 2,
              decoration: InputDecoration(
                labelText: tr('Private order cancellation reason'),
              ),
            ),
            CheckboxListTile(
              value: _confirmed,
              onChanged: (v) => setState(() => _confirmed = v == true),
              title: Text(
                tr(
                  'Cancel only this uncollected order and return money to its original wallet.',
                ),
              ),
            ),
            FilledButton(
              onPressed: !_confirmed || _reason.text.trim().length < 3
                  ? null
                  : () {
                      context.read<CommerceCubit>().cancel(
                        order.id,
                        _reason.text,
                      );
                    },
              child: Text(tr('Cancel uncollected order')),
            ),
          ],
        ],
      ),
    );
  }
}
