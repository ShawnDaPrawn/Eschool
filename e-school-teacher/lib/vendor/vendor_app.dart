import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import 'vendor_repository.dart';
import 'vendor_nfc.dart';
import 'vendor_payment_screen.dart';

String _tr(BuildContext context, String text) =>
    AppLocalization.of(context)?.getTranslatedValues(text) ?? text;
String _money(String currency, int cents) =>
    '$currency ${cents ~/ 100}.${(cents % 100).toString().padLeft(2, '0')}';

class VendorApp extends StatefulWidget {
  const VendorApp({
    required this.repository,
    this.scanQr,
    this.readCard,
    super.key,
  });
  final Future<VendorSunTap?> Function(int school)? readCard;
  final VendorRepository repository;
  final Future<String?> Function(BuildContext)? scanQr;
  @override
  State<VendorApp> createState() => _VendorAppState();
}

class _VendorAppState extends State<VendorApp> with WidgetsBindingObserver {
  bool hidden = false;
  Locale locale = const Locale('en');
  static const languages = [
    AppLanguage(languageCode: 'en', languageName: 'English'),
    AppLanguage(languageCode: 'af', languageName: 'Afrikaans'),
    AppLanguage(languageCode: 'zu', languageName: 'isiZulu'),
    AppLanguage(languageCode: 'ar', languageName: 'العربية'),
  ];
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      setState(() => hidden = false);
      widget.repository.resume();
    } else if ([
      AppLifecycleState.paused,
      AppLifecycleState.hidden,
      AppLifecycleState.inactive,
    ].contains(state)) {
      setState(() => hidden = true);
      widget.repository.suspend();
    }
  }

  @override
  Widget build(BuildContext context) => AnimatedBuilder(
    animation: widget.repository,
    builder: (_, _) {
      final repo = widget.repository;
      return MaterialApp(
        key: ValueKey('${repo.profile?.schoolId}:${repo.profile?.userId}'),
        title: 'NavigateEd Vendor',
        theme: AppTheme.light(),
        locale: locale,
        supportedLocales: [for (final l in languages) Locale(l.languageCode)],
        localizationsDelegates: const [
          AppLocalizationDelegate(languages),
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: Builder(
          builder: (context) => hidden
              ? const Scaffold(body: SizedBox.shrink())
              : Scaffold(
                  appBar: AppBar(
                    title: Text(_tr(context, 'NavigateEd Vendor')),
                    actions: [
                      DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: locale.languageCode,
                          items: [
                            for (final l in languages)
                              DropdownMenuItem(
                                value: l.languageCode,
                                child: Text(l.languageName),
                              ),
                          ],
                          onChanged: (value) {
                            if (value != null) {
                              setState(() => locale = Locale(value));
                            }
                          },
                        ),
                      ),
                      if (repo.signedIn)
                        IconButton(
                          tooltip: _tr(context, 'Sign out'),
                          onPressed: () async {
                            try {
                              await repo.signOut();
                            } catch (_) {
                              /* Local private session is already cleared. */
                            }
                          },
                          icon: const Icon(Icons.logout),
                        ),
                    ],
                  ),
                  body: repo.signedIn
                      ? _VendorCounterView(
                          repository: repo,
                          scanQr: widget.scanQr,
                          readCard: widget.readCard,
                        )
                      : _VendorSignIn(repository: repo),
                ),
        ),
      );
    },
  );
}

class _VendorSignIn extends StatefulWidget {
  const _VendorSignIn({required this.repository});
  final VendorRepository repository;
  @override
  State<_VendorSignIn> createState() => _VendorSignInState();
}

class _VendorSignInState extends State<_VendorSignIn> {
  final school = TextEditingController(),
      email = TextEditingController(),
      password = TextEditingController(),
      newPassword = TextEditingController(),
      confirm = TextEditingController();
  final accepted = <String, bool>{};
  final form = GlobalKey<FormState>();
  @override
  void dispose() {
    for (final c in [school, email, password, newPassword, confirm]) {
      c.clear();
      c.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final repo = widget.repository;
    final setup = repo.setup;
    Widget field(
      String label,
      TextEditingController controller, {
      bool secret = false,
      int max = 191,
    }) => TextFormField(
      controller: controller,
      obscureText: secret,
      enableSuggestions: !secret,
      autocorrect: !secret,
      autofillHints: secret ? const [] : null,
      maxLength: max,
      decoration: InputDecoration(labelText: _tr(context, label)),
      validator: (v) => (v ?? '').trim().isEmpty
          ? _tr(context, 'Complete this field.')
          : null,
    );
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(
          _tr(context, 'Vendor sign-in'),
          style: Theme.of(context).textTheme.headlineMedium,
        ),
        Text(
          _tr(
            context,
            'Use the dedicated vendor identity and school code supplied by Finance.',
          ),
        ),
        if (repo.error != null)
          Text(
            _tr(context, repo.error!),
            semanticsLabel: _tr(context, repo.error!),
          ),
        if (repo.busy)
          LinearProgressIndicator(
            semanticsLabel: _tr(context, 'Checking vendor access'),
          ),
        Form(
          key: form,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              field('School code', school),
              field('Email or handle', email),
              field('Password', password, secret: true, max: 128),
              if (setup?.passwordRequired == true) ...[
                field(
                  'Choose a new password',
                  newPassword,
                  secret: true,
                  max: 72,
                ),
                field('Confirm new password', confirm, secret: true, max: 72),
              ],
              for (final doc
                  in setup?.documents ?? <VendorPolicyDocument>[]) ...[
                Text(doc.title, style: Theme.of(context).textTheme.titleLarge),
                Text(doc.text),
                CheckboxListTile(
                  value: accepted[doc.version] == true,
                  onChanged: (v) =>
                      setState(() => accepted[doc.version] = v == true),
                  title: Text(
                    _tr(context, 'I read and accept these current terms.'),
                  ),
                ),
              ],
              FilledButton(
                onPressed: repo.busy
                    ? null
                    : () async {
                        if (form.currentState?.validate() != true) return;
                        if ((setup?.documents ?? []).any(
                          (d) => accepted[d.version] != true,
                        )) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(
                                _tr(
                                  context,
                                  'Read and accept the current policies.',
                                ),
                              ),
                            ),
                          );
                          return;
                        }
                        await repo.signIn({
                          'school': school.text.trim(),
                          'email': email.text.trim(),
                          'password': password.text,
                          if (setup?.passwordRequired == true)
                            'new_password': newPassword.text,
                          if (setup?.passwordRequired == true)
                            'new_password_confirmation': confirm.text,
                          'acceptances': {
                            for (final doc
                                in setup?.documents ?? <VendorPolicyDocument>[])
                              if (accepted[doc.version] == true)
                                doc.document: doc.version,
                          },
                        });
                      },
                child: Text(_tr(context, 'Sign in to vendor counter')),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _VendorCounterView extends StatefulWidget {
  const _VendorCounterView({
    required this.repository,
    this.scanQr,
    this.readCard,
  });
  final Future<VendorSunTap?> Function(int school)? readCard;
  final VendorRepository repository;
  final Future<String?> Function(BuildContext)? scanQr;
  @override
  State<_VendorCounterView> createState() => _VendorCounterViewState();
}

class _VendorCounterViewState extends State<_VendorCounterView> {
  final code = TextEditingController();
  bool confirmed = false;
  bool admissionMode = false;
  @override
  void dispose() {
    code.clear();
    code.dispose();
    super.dispose();
  }

  Future<void> lookup(String value) async {
    code.clear();
    setState(() => confirmed = false);
    if (admissionMode) {
      await widget.repository.lookupAdmission(value);
    } else {
      await widget.repository.lookup(value);
    }
  }

  Future<void> camera() async {
    code.clear();
    setState(() => confirmed = false);
    final repo = widget.repository;
    final mode = admissionMode;
    final school = repo.profile?.schoolId,
        actor = repo.profile?.userId,
        counter = repo.counter?.id;
    final value =
        await (widget.scanQr?.call(context) ??
            Navigator.of(context).push<String>(
              MaterialPageRoute(
                builder: (_) => VendorOrderCamera(admission: mode),
              ),
            ));
    await repo.idle;
    if (value != null &&
        repo.profile?.schoolId == school &&
        repo.profile?.userId == actor &&
        repo.counter?.id == counter &&
        admissionMode == mode) {
      if (mode) {
        await repo.lookupAdmission(value);
      } else {
        await repo.lookup(value);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final repo = widget.repository,
        profile = repo.profile!,
        counter = repo.counter!;
    final receipt = repo.receipt;
    return ListView(
      key: const ValueKey('vendor-counter-scroll'),
      padding: const EdgeInsets.all(20),
      children: [
        Text(profile.schoolName, style: Theme.of(context).textTheme.titleLarge),
        Text(profile.name),
        if (profile.walletPaymentsAvailable)
          TextButton.icon(
            onPressed: repo.busy
                ? null
                : () async {
                    await Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => VendorWalletPaymentScreen(
                          repository: repo,
                          readCard: widget.readCard,
                          scanQr: (screenContext) =>
                              widget.scanQr?.call(screenContext) ??
                              Navigator.of(screenContext).push<String>(
                                MaterialPageRoute(
                                  builder: (_) => const VendorOrderCamera(
                                    walletPayment: true,
                                  ),
                                ),
                              ),
                        ),
                      ),
                    );
                    if (mounted && repo.signedIn) await repo.refresh();
                  },
            icon: const Icon(Icons.qr_code),
            label: Text(_tr(context, 'Wallet payments')),
          ),
        DropdownButtonFormField<int>(
          initialValue: counter.id,
          key: ValueKey(counter.id),
          isExpanded: true,
          decoration: InputDecoration(
            labelText: _tr(context, 'Assigned counter'),
          ),
          items: [
            for (final c in profile.counters)
              DropdownMenuItem(value: c.id, child: Text(c.name)),
          ],
          onChanged: repo.busy
              ? null
              : (id) {
                  if (id != null) {
                    code.clear();
                    setState(() => confirmed = false);
                    repo.refresh(vendor: id);
                  }
                },
        ),
        Wrap(
          spacing: 12,
          children: [
            OutlinedButton.icon(
              onPressed: repo.busy
                  ? null
                  : () {
                      code.clear();
                      setState(() => confirmed = false);
                      repo.refresh();
                    },
              icon: const Icon(Icons.refresh),
              label: Text(_tr(context, 'Refresh assigned counter')),
            ),
            DropdownButton<String>(
              value: repo.filter,
              items: [
                for (final status in ['ready', 'collected', 'cancelled'])
                  DropdownMenuItem(
                    value: status,
                    child: Text(
                      _tr(
                        context,
                        {
                          'ready': 'Ready for collection',
                          'collected': 'Collected',
                          'cancelled': 'Cancelled',
                        }[status]!,
                      ),
                    ),
                  ),
              ],
              onChanged: repo.busy
                  ? null
                  : (status) {
                      if (status != null) repo.refresh(status: status);
                    },
            ),
          ],
        ),
        if (repo.busy)
          LinearProgressIndicator(
            semanticsLabel: _tr(context, 'Checking vendor orders'),
          ),
        if (!counter.canCollect)
          Text(
            _tr(
              context,
              'This counter is read-only. Keep history; do not hand over goods.',
            ),
          ),
        if (repo.error != null)
          Text(
            _tr(context, repo.error!),
            semanticsLabel: _tr(context, repo.error!),
          ),
        if (repo.message != null)
          Semantics(liveRegion: true, child: Text(_tr(context, repo.message!))),
        if (repo.hasPending) ...[
          Text(
            _tr(
              context,
              'A handover request needs an outcome check before another confirmation.',
            ),
          ),
          FilledButton(
            onPressed: repo.busy ? null : () => repo.recover(),
            child: Text(
              _tr(
                context,
                repo.hasPendingAdmission
                    ? 'Check admission outcome'
                    : 'Check handover outcome',
              ),
            ),
          ),
        ],
        if (profile.ticketingAvailable)
          Wrap(
            spacing: 12,
            children: [
              TextButton(
                onPressed: repo.busy
                    ? null
                    : () {
                        code.clear();
                        setState(() {
                          admissionMode = false;
                          confirmed = false;
                        });
                        repo.refresh();
                      },
                child: Text(_tr(context, 'Physical order handovers')),
              ),
              TextButton(
                onPressed: repo.busy
                    ? null
                    : () {
                        code.clear();
                        setState(() {
                          admissionMode = true;
                          confirmed = false;
                        });
                        repo.refresh();
                      },
                child: Text(_tr(context, 'Event admissions')),
              ),
            ],
          ),
        TextField(
          controller: code,
          enabled: !repo.busy,
          maxLength: admissionMode ? 82 : 81,
          autocorrect: false,
          enableSuggestions: false,
          decoration: InputDecoration(
            labelText: _tr(
              context,
              admissionMode
                  ? 'Current ticket number or admission QR'
                  : 'Order number or wallet order QR',
            ),
          ),
        ),
        Wrap(
          spacing: 12,
          children: [
            FilledButton(
              onPressed: repo.busy ? null : () => lookup(code.text),
              child: Text(
                _tr(
                  context,
                  admissionMode
                      ? 'Review admission before entry'
                      : 'Review order before handover',
                ),
              ),
            ),
            OutlinedButton.icon(
              onPressed: repo.busy ? null : camera,
              icon: const Icon(Icons.qr_code_scanner),
              label: Text(
                _tr(
                  context,
                  admissionMode
                      ? 'Scan admission QR with camera'
                      : 'Scan order QR with camera',
                ),
              ),
            ),
          ],
        ),
        if (repo.admission case final admission? when admissionMode)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    admission.eventName,
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  Text(admission.productName),
                  if (admission.namedLearner != null)
                    Text(
                      '${_tr(context, 'Original named learner')}: ${admission.namedLearner}',
                    ),
                  if (admission.status == 'redeemed')
                    Text(
                      '${_tr(context, 'Original admission recorded at')}: ${admission.redeemedAt!.toLocal()} · ${_tr(context, 'Operator')}: ${admission.redeemedBy}',
                    ),
                  if (admission.canRedeem) ...[
                    CheckboxListTile(
                      value: confirmed,
                      onChanged: repo.busy || repo.hasPending
                          ? null
                          : (v) => setState(() => confirmed = v == true),
                      title: Text(
                        _tr(
                          context,
                          'I verified this original admission and will admit it once.',
                        ),
                      ),
                    ),
                    FilledButton(
                      onPressed: !confirmed || repo.busy || repo.hasPending
                          ? null
                          : () {
                              code.clear();
                              setState(() => confirmed = false);
                              repo.admit();
                            },
                      child: Text(_tr(context, 'Confirm one actual admission')),
                    ),
                  ] else if (admission.status != 'redeemed')
                    Text(
                      _tr(
                        context,
                        'This admission is unavailable under the current event window, holder or school rules. Keep its original record.',
                      ),
                    ),
                ],
              ),
            ),
          ),
        if (!admissionMode) ...[
          if (receipt != null)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      receipt.number,
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    Text(
                      _tr(
                        context,
                        {
                          'ready': 'Ready for collection',
                          'collected': 'Collected',
                          'cancelled': 'Cancelled',
                        }[receipt.status]!,
                      ),
                    ),
                    Text(_money(receipt.currency, receipt.amountCents)),
                    for (final line in receipt.lines)
                      Text(
                        '${line.name} · ${line.quantity} ${line.unit} · ${_money(receipt.currency, line.grossCents)}',
                      ),
                    Text(
                      '${_tr(context, 'VAT included')}: ${_money(receipt.currency, receipt.vatCents)}',
                    ),
                    if (receipt.processedAt != null)
                      Text(
                        '${_tr(context, 'Processed at')}: ${receipt.processedAt!.toIso8601String()}',
                      ),
                    if (receipt.canCollect && !repo.hasPending) ...[
                      CheckboxListTile(
                        value: confirmed,
                        onChanged: repo.busy
                            ? null
                            : (v) => setState(() => confirmed = v == true),
                        title: Text(
                          _tr(
                            context,
                            'I reviewed these exact goods and will hand them over once after successful validation.',
                          ),
                        ),
                      ),
                      FilledButton(
                        onPressed: repo.busy || !confirmed
                            ? null
                            : () {
                                setState(() => confirmed = false);
                                repo.collect();
                              },
                        child: Text(_tr(context, 'Validate one handover')),
                      ),
                    ] else
                      Text(
                        _tr(
                          context,
                          'This order cannot be handed over again. Check its recorded status before supplying goods.',
                        ),
                      ),
                  ],
                ),
              ),
            ),
          OutlinedButton(
            onPressed: repo.busy ? null : () => repo.loadSettlements(),
            child: Text(_tr(context, 'View this counter settlement history')),
          ),
          if (repo.settlementView) ...[
            Text(
              _tr(context, 'Your counter settlements'),
              style: Theme.of(context).textTheme.titleLarge,
            ),
            if (repo.settlements.isEmpty && repo.settlement == null)
              Text(
                _tr(
                  context,
                  'No vendor settlement batches have been prepared.',
                ),
              ),
            for (final row in repo.settlements)
              ListTile(
                title: Text(
                  '${_tr(context, 'Settlement')} ${row.id} · ${row.periodFrom}–${row.periodTo}',
                ),
                subtitle: Text(
                  '${_money(row.currency, row.payoutCents)} · ${_tr(context, {'prepared': 'Prepared', 'approved': 'Approved', 'settled': 'Settled'}[row.status]!)}',
                ),
                onTap: repo.busy ? null : () => repo.openSettlement(row.id),
              ),
            if (repo.settlementPage > 1 && repo.settlements.isNotEmpty)
              TextButton(
                onPressed: repo.busy
                    ? null
                    : () => repo.loadSettlements(
                        pageNumber: repo.settlementPage - 1,
                      ),
                child: Text(_tr(context, 'Previous settlements')),
              ),
            if (repo.settlementPage < repo.lastSettlementPage &&
                repo.settlements.isNotEmpty)
              TextButton(
                onPressed: repo.busy
                    ? null
                    : () => repo.loadSettlements(
                        pageNumber: repo.settlementPage + 1,
                      ),
                child: Text(_tr(context, 'More settlements')),
              ),
            if (repo.settlement case final row?)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        '${_tr(context, 'Settlement')} ${row.id}',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      Text(
                        '${row.periodFrom}–${row.periodTo} · ${_tr(context, {'prepared': 'Prepared', 'approved': 'Approved', 'settled': 'Settled'}[row.status]!)}',
                      ),
                      Text(
                        '${_tr(context, 'Original gross')}: ${_money(row.currency, row.grossCents)}',
                      ),
                      Text(
                        '${_tr(context, 'VAT-exclusive sales net')}: ${_money(row.currency, row.salesNetCents)}',
                      ),
                      Text(
                        '${_tr(context, 'VAT included')}: ${_money(row.currency, row.vatCents)}',
                      ),
                      Text(
                        '${_tr(context, 'Collector fee')}: ${_money(row.currency, row.collectorFeeCents)}',
                      ),
                      Text(
                        '${_tr(context, 'Vendor settlement amount')}: ${_money(row.currency, row.payoutCents)}',
                      ),
                      if (row.paidOn != null)
                        Text(
                          '${_tr(context, 'Actual settlement date')}: ${row.paidOn}',
                        ),
                      for (final line in row.lines)
                        Text(
                          '${line.number} · ${_money(row.currency, line.grossCents)}',
                        ),
                    ],
                  ),
                ),
              ),
          ],
          Text(
            _tr(context, 'Orders at this counter'),
            style: Theme.of(context).textTheme.titleLarge,
          ),
          for (final order in repo.orders)
            ListTile(
              title: Text(order.number),
              subtitle: Text(
                '${_money(order.currency, order.amountCents)} · ${_tr(context, {'ready': 'Ready for collection', 'collected': 'Collected', 'cancelled': 'Cancelled'}[order.status]!)}',
              ),
              onTap: repo.busy
                  ? null
                  : () {
                      setState(() => confirmed = false);
                      repo.openOrder(order.id);
                    },
            ),
          Wrap(
            spacing: 12,
            children: [
              if (repo.page > 1)
                OutlinedButton(
                  onPressed: repo.busy
                      ? null
                      : () => repo.refresh(
                          nextPage: repo.page - 1,
                          status: repo.filter,
                        ),
                  child: Text(_tr(context, 'Previous orders')),
                ),
              if (repo.page < repo.lastPage)
                OutlinedButton(
                  onPressed: repo.busy
                      ? null
                      : () => repo.refresh(
                          nextPage: repo.page + 1,
                          status: repo.filter,
                        ),
                  child: Text(_tr(context, 'More orders')),
                ),
            ],
          ),
        ],
      ],
    );
  }
}

class VendorOrderCamera extends StatefulWidget {
  const VendorOrderCamera({
    this.admission = false,
    this.walletPayment = false,
    super.key,
  });
  final bool admission, walletPayment;
  @override
  State<VendorOrderCamera> createState() => _VendorOrderCameraState();
}

class _VendorOrderCameraState extends State<VendorOrderCamera> {
  bool received = false;
  @override
  void initState() {
    super.initState();
    if (kIsWeb) {
      MobileScannerPlatform.instance.setWebBarcodeReader(
        WebBarcodeReader.barcodeDetector,
      );
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text(
        _tr(
          context,
          widget.walletPayment
              ? 'Scan wallet payment QR'
              : widget.admission
              ? 'Scan admission QR with camera'
              : 'Scan order QR with camera',
        ),
      ),
    ),
    body: MobileScanner(
      onDetect: (capture) {
        if (received) return;
        for (final barcode in capture.barcodes) {
          final value = barcode.rawValue;
          if (value != null &&
              (widget.walletPayment
                      ? RegExp(r'^navigateed:wallet-pay:[a-f0-9]{64}$')
                      : widget.admission
                      ? RegExp(r'^navigateed:ticket:[a-f0-9]{64}$')
                      : RegExp(r'^navigateed:order:[a-f0-9]{64}$'))
                  .hasMatch(value)) {
            received = true;
            Navigator.of(context).pop(value);
            break;
          }
        }
      },
      errorBuilder: (_, _) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(
            _tr(
              context,
              'Camera scanning is unavailable here. Type the order number or paste its QR code.',
            ),
          ),
        ),
      ),
    ),
  );
}
