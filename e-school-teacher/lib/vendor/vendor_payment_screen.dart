import 'dart:async';

import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

import 'vendor_nfc.dart';
import 'vendor_repository.dart';

String _paymentText(BuildContext context, String value) =>
    AppLocalization.of(context)?.getTranslatedValues(value) ?? value;
String _paymentMoney(String currency, int cents) =>
    '$currency ${cents ~/ 100}.${(cents % 100).toString().padLeft(2, '0')}';

class VendorWalletPaymentScreen extends StatefulWidget {
  const VendorWalletPaymentScreen({
    required this.repository,
    required this.scanQr,
    this.readCard,
    super.key,
  });
  final VendorRepository repository;
  final Future<String?> Function(BuildContext) scanQr;
  final Future<VendorSunTap?> Function(int school)? readCard;
  @override
  State<VendorWalletPaymentScreen> createState() => _VendorWalletPaymentState();
}

class _VendorWalletPaymentState extends State<VendorWalletPaymentScreen>
    with WidgetsBindingObserver {
  final _qr = TextEditingController(), _reader = TextEditingController();
  final Map<int, int> _cart = {};
  final _nfc = VendorNfc();
  final _scroll = ScrollController();
  late final int _generation, _school, _actor, _counter;
  String? _reviewId, _localMessage;
  bool _confirmed = false, _reading = false;
  Timer? _expiry;
  bool get _active => widget.repository.payments.matches(
    _generation,
    _school,
    _actor,
    _counter,
  );
  @override
  void initState() {
    super.initState();
    final repo = widget.repository;
    _generation = repo.generation;
    _school = repo.profile!.schoolId;
    _actor = repo.profile!.userId;
    _counter = repo.counter!.id;
    WidgetsBinding.instance.addObserver(this);
    repo.addListener(_changed);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && _active) repo.payments.load();
    });
  }

  void _changed() {
    final review = widget.repository.payments.review?.instruction;
    if (review?.id != _reviewId) {
      _confirmed = false;
      _expiry?.cancel();
      _reviewId = review?.id;
      if (review != null) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted && _active && _scroll.hasClients) _scroll.jumpTo(0);
        });
      }
      if (review != null && review.expiresAt.isAfter(DateTime.now().toUtc())) {
        _expiry = Timer(
          review.expiresAt.difference(DateTime.now().toUtc()),
          () {
            if (mounted && _active) widget.repository.payments.load();
          },
        );
      }
    }
    if (!_active) {
      _qr.clear();
      _reader.clear();
      _cart.clear();
      _confirmed = false;
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _qr.clear();
      _reader.clear();
      _cart.clear();
      _expiry?.cancel();
      _confirmed = false;
      _nfc.cancel().catchError((_) {});
    }
  }

  @override
  void dispose() {
    _expiry?.cancel();
    widget.repository.payments.clearViews();
    widget.repository.removeListener(_changed);
    WidgetsBinding.instance.removeObserver(this);
    _qr.dispose();
    _reader.dispose();
    _scroll.dispose();
    _nfc.cancel().catchError((_) {});
    super.dispose();
  }

  Future<void> _lookup(String code) async {
    _qr.clear();
    _reader.clear();
    _confirmed = false;
    if (_active) await widget.repository.payments.lookup(code);
  }

  Future<void> _camera() async {
    _qr.clear();
    _confirmed = false;
    final value = await widget.scanQr(context);
    if (mounted && _active && value != null) await _lookup(value);
  }

  Future<void> _tap() async {
    final cart = Map<int, int>.from(_cart);
    setState(() {
      _reading = true;
      _confirmed = false;
      _localMessage = null;
    });
    try {
      final proof =
          await (widget.readCard?.call(_school) ?? _nfc.read(_school));
      if (mounted && _active && proof != null) {
        await widget.repository.payments.prepare(proof, cart);
      }
    } catch (_) {
      if (mounted && _active) _localMessage = 'Card tap could not be read. Check this device or use the wallet QR.';
    } finally {
      if (mounted) setState(() => _reading = false);
    }
  }

  @override
  Widget build(BuildContext context) => AnimatedBuilder(
    animation: widget.repository,
    builder: (context, _) {
      String tr(String value) => _paymentText(context, value);
      if (!_active) {
        return ScreenScaffold(
          title: tr('Wallet payments'),
          body: Center(
            child: Text(
              tr('Vendor access changed. Return to the counter and refresh.'),
            ),
          ),
        );
      }
      final repo = widget.repository, payments = repo.payments;
      final catalogue = payments.catalogue, review = payments.review;
      final busy = repo.busy || _reading;
      final canPay =
          !busy &&
          !repo.hasPending &&
          _confirmed &&
          review != null &&
          review.instruction.expiresAt.isAfter(DateTime.now().toUtc());
      return ScreenScaffold(
        title: tr('Wallet payments'),
        body: ListView(
          controller: _scroll,
          padding: const EdgeInsets.all(20),
          children: [
            Text(
              repo.counter!.name,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            if (busy) const LinearProgressIndicator(),
            if (repo.error != null)
              Text(tr(repo.error!), semanticsLabel: tr(repo.error!)),
            if (_localMessage != null) Text(tr(_localMessage!)),
            if (repo.message != null)
              Semantics(liveRegion: true, child: Text(tr(repo.message!))),
            if (repo.hasPending) ...[
              Text(
                tr(
                  'Check the original counter request before another payment or handover.',
                ),
              ),
              FilledButton(
                onPressed: busy ? null : repo.recover,
                child: Text(tr('Check original counter outcome')),
              ),
            ],
            TextField(
              controller: _qr,
              enabled: !busy && !repo.hasPending,
              maxLength: 86,
              decoration: InputDecoration(
                labelText: tr('Current wallet payment QR'),
              ),
              autocorrect: false,
              enableSuggestions: false,
            ),
            Wrap(
              spacing: 12,
              children: [
                FilledButton(
                  onPressed: busy || repo.hasPending
                      ? null
                      : () => _lookup(_qr.text),
                  child: Text(tr('Review wallet QR payment')),
                ),
                OutlinedButton(
                  onPressed: busy || repo.hasPending ? null : _camera,
                  child: Text(tr('Scan wallet payment QR')),
                ),
              ],
            ),
            if (review != null) ...[
              Text(
                tr('Review this counter payment'),
                style: Theme.of(context).textTheme.titleLarge,
              ),
              for (final line in review.instruction.lines)
                Text(
                  '${line.productName} · ${line.quantity} · ${_paymentMoney(review.instruction.currency, line.grossCents)}',
                ),
              Text(
                '${tr('Exact wallet total')}: ${_paymentMoney(review.instruction.currency, review.instruction.amountCents)}',
              ),
              if (review.holderName != null) ...[
                Text(review.holderName!),
                if (payments.portrait case final photo?)
                  Image.memory(
                    photo,
                    height: 180,
                    fit: BoxFit.contain,
                    semanticLabel: tr('Current cardholder photo'),
                  ),
              ],
              CheckboxListTile(
                value: _confirmed,
                onChanged: busy || repo.hasPending
                    ? null
                    : (v) => setState(() => _confirmed = v == true),
                title: Text(
                  tr(
                    review.holderName == null
                        ? 'I checked the exact items and total and will hand over these goods once.'
                        : 'I checked this current photo, the exact items and total, and will hand over these goods once.',
                  ),
                ),
              ),
              FilledButton(
                onPressed: canPay
                    ? () => payments.pay(photoConfirmed: _confirmed)
                    : null,
                child: Text(tr('Confirm wallet payment and handover')),
              ),
            ],
            if (payments.receipt case final receipt?) ...[
              Text('${tr('Original order receipt')}: ${receipt.number}'),
              Text(_paymentMoney(receipt.currency, receipt.amountCents)),
              for (final line in receipt.lines)
                Text('${line.name} · ${line.quantity}'),
            ],
            const Divider(),
            Text(
              tr('Pay by school identity card'),
              style: Theme.of(context).textTheme.titleLarge,
            ),
            Text(
              tr(
                'Select the goods, tap the card, then check its photo and the exact amount before confirming.',
              ),
            ),
            TextField(
              controller: _reader,
              enabled: !busy,
              obscureText: true,
              autocorrect: false,
              enableSuggestions: false,
              decoration: InputDecoration(
                labelText: tr('Private reader pairing credential'),
              ),
            ),
            TextButton(
              onPressed: busy
                  ? null
                  : () {
                      final value = _reader.text;
                      _reader.clear();
                      final paired = payments.pair(value);
                      setState(
                        () => _localMessage = paired
                            ? 'Reader paired for this counter session.'
                            : 'Use the private reader credential for this school and counter.',
                      );
                    },
              child: Text(tr('Pair payment reader')),
            ),
            if (payments.paired)
              Text(tr('Reader paired for this counter session.')),
            if (catalogue != null) ...[
              for (final item in catalogue.items)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(item.name),
                        Text(
                          _paymentMoney(
                            catalogue.currency,
                            item.unitPriceCents,
                          ),
                        ),
                        DropdownButtonFormField<int>(
                          initialValue: _cart[item.id] ?? 0,
                          key: ValueKey('${item.id}:${_cart[item.id] ?? 0}'),
                          decoration: InputDecoration(
                            labelText: tr('Quantity'),
                          ),
                          items: [
                            for (var i = 0; i <= 20; i++)
                              DropdownMenuItem(value: i, child: Text('$i')),
                          ],
                          onChanged: busy || repo.hasPending
                              ? null
                              : (v) => setState(() {
                                  if (v == null || v == 0) {
                                    _cart.remove(item.id);
                                  } else {
                                    _cart[item.id] = v;
                                  }
                                  _confirmed = false;
                                }),
                        ),
                      ],
                    ),
                  ),
                ),
              Text(
                '${tr('Selected goods total')}: ${_paymentMoney(catalogue.currency, catalogue.items.fold<int>(0, (sum, item) => sum + item.unitPriceCents * (_cart[item.id] ?? 0)))}',
              ),
              FilledButton.icon(
                onPressed:
                    busy || repo.hasPending || !payments.paired || _cart.isEmpty
                    ? null
                    : _tap,
                icon: const Icon(Icons.nfc),
                label: Text(tr('Tap school card for these goods')),
              ),
              if (catalogue.page > 1)
                TextButton(
                  onPressed: busy
                      ? null
                      : () {
                          _cart.clear();
                          payments.load(page: catalogue.page - 1);
                        },
                  child: Text(tr('Previous products')),
                ),
              if (catalogue.page < catalogue.lastPage)
                TextButton(
                  onPressed: busy
                      ? null
                      : () {
                          _cart.clear();
                          payments.load(page: catalogue.page + 1);
                        },
                  child: Text(tr('More products')),
                ),
            ],
            TextButton(
              onPressed: busy
                  ? null
                  : () {
                      _cart.clear();
                      payments.load();
                    },
              child: Text(tr('Refresh counter items')),
            ),
          ],
        ),
      );
    },
  );
}
