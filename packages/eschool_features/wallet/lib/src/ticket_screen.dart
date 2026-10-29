import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:qr_flutter/qr_flutter.dart';

import 'ticket_cubit.dart';
import 'ticket_repository.dart';
import 'wallet_text.dart';

class TicketScreen extends StatelessWidget {
  const TicketScreen({required this.repository, super.key});
  final TicketRepository repository;
  @override
  Widget build(BuildContext context) => BlocProvider(
    create: (_) => TicketCubit(repository)..load(),
    child: const _TicketsView(),
  );
}

class _TicketsView extends StatefulWidget {
  const _TicketsView();
  @override
  State<_TicketsView> createState() => _TicketsViewState();
}

class _TicketsViewState extends State<_TicketsView>
    with WidgetsBindingObserver {
  final _code = TextEditingController();
  int? _ticket;
  bool _confirmed = false;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _code.clear();
    _ticket = null;
    _confirmed = false;
    final cubit = context.read<TicketCubit>();
    if (state == AppLifecycleState.resumed) {
      cubit.load();
    } else {
      cubit.suspend();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _code.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => BlocConsumer<TicketCubit, TicketState>(
    listener: (_, state) {
      if (state.accessLost) {
        _code.clear();
        _ticket = null;
        _confirmed = false;
      }
    },
    builder: (context, state) {
      final cubit = context.read<TicketCubit>();
      String tr(String s) => walletText(context, s);
      final choices =
          state.page?.items.where((t) => t.canTransfer).toList() ??
          <AdmissionTicket>[];
      final selected = choices.any((t) => t.id == _ticket)
          ? _ticket
          : choices.firstOrNull?.id;
      return ScreenScaffold(
        title: tr('Your admission tickets'),
        action: IconButton(
          tooltip: tr('Refresh tickets'),
          icon: const Icon(Icons.refresh),
          onPressed: state.busy
              ? null
              : () {
                  _code.clear();
                  _confirmed = false;
                  cubit.load();
                },
        ),
        body: ListView(
          key: const ValueKey('ticket-screen-scroll'),
          padding: const EdgeInsets.all(16),
          children: [
            if (state.busy)
              LinearProgressIndicator(
                semanticsLabel: tr('Loading admission tickets'),
              ),
            if (state.error != null) Text(tr(state.error!)),
            if (state.accessLost)
              Text(tr('Your ticket access changed. Reopen Wallets.')),
            if (state.message != null)
              Semantics(liveRegion: true, child: Text(tr(state.message!))),
            if (cubit.hasPending)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        tr(
                          'An original request needs an outcome check before another transfer.',
                        ),
                      ),
                      FilledButton(
                        onPressed: state.busy
                            ? null
                            : () {
                                _code.clear();
                                cubit.recover();
                              },
                        child: Text(
                          tr('Check original ticket request outcomes'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            if (state.page != null) ...[
              FilledButton.tonal(
                onPressed: state.busy
                    ? null
                    : () {
                        _code.clear();
                        _confirmed = false;
                        cubit.receivingCode();
                      },
                child: Text(tr('Show my receiving code')),
              ),
              if (state.receiving != null)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          tr(
                            'Share this short-lived receiving code only with the person sending you a ticket.',
                          ),
                        ),
                        SelectableText(state.receiving!.receiveCode),
                        Text(
                          '${tr('Receiving code expires')}: ${state.receiving!.expiresAt.toLocal()}',
                        ),
                        TextButton(
                          onPressed: () => Clipboard.setData(
                            ClipboardData(text: state.receiving!.receiveCode),
                          ),
                          child: Text(tr('Copy receiving code')),
                        ),
                      ],
                    ),
                  ),
                ),
              if (state.page!.items.isEmpty)
                Text(
                  tr(
                    'No admission tickets are currently held by you or your authorised children.',
                  ),
                ),
              for (final ticket in state.page!.items)
                Card(
                  key: ValueKey('admission-ticket-${ticket.id}'),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          ticket.event.name,
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        Text(ticket.productName),
                        if (ticket.namedLearnerName != null)
                          Text(
                            '${tr('Original named learner')}: ${ticket.namedLearnerName}',
                          ),
                        Text(
                          '${tr('Admission opens in your local time')}: ${ticket.event.startsAt.toLocal()}',
                        ),
                        Text(
                          '${tr('Admission closes in your local time')}: ${ticket.event.endsAt.toLocal()}',
                        ),
                        Text(
                          '${tr('Original paid admission')}: ${walletAmount(ticket.currency, ticket.unitPriceCents)} · ${ticket.orderNumber}',
                        ),
                        if (ticket.qr != null) ...[
                          Center(
                            child: Semantics(
                              label: tr('Private admission QR'),
                              child: QrImageView(
                                data: ticket.qr!,
                                size: 176,
                                backgroundColor: Colors.white,
                              ),
                            ),
                          ),
                          SelectableText(ticket.ticketNumber!),
                        ],
                        if (ticket.status == AdmissionTicketStatusEnum.redeemed)
                          Text(
                            '${tr('Original admission recorded at')}: ${ticket.redeemedAt!.toLocal()} · ${tr('Operator')}: ${ticket.redeemedBy}',
                          ),
                        if (ticket.qr == null &&
                            ticket.status != AdmissionTicketStatusEnum.redeemed)
                          Text(
                            tr(
                              'This ticket is retained. Current event rules do not allow admission.',
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              if (choices.isNotEmpty && state.preview == null) ...[
                Text(
                  tr('Transfer an original ticket'),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                DropdownButtonFormField<int>(
                  key: ValueKey('ticket-transfer-choice-$selected'),
                  initialValue: selected,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: tr('Ticket to transfer'),
                  ),
                  items: [
                    for (final t in choices)
                      DropdownMenuItem(
                        value: t.id,
                        child: Text(
                          '${t.event.name} · ${t.id}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                  ],
                  onChanged: cubit.hasPending
                      ? null
                      : (v) => setState(() => _ticket = v),
                ),
                TextField(
                  controller: _code,
                  enabled: !state.busy && !cubit.hasPending,
                  maxLength: 8192,
                  maxLines: 2,
                  decoration: InputDecoration(
                    labelText: tr('Intended recipient receiving code'),
                  ),
                ),
                FilledButton(
                  onPressed: state.busy || cubit.hasPending || selected == null
                      ? null
                      : () {
                          _ticket = selected;
                          _confirmed = false;
                          final code = _code.text.trim();
                          _code.clear();
                          cubit.preview(selected, code);
                        },
                  child: Text(tr('Review intended ticket recipient')),
                ),
              ],
              if (state.preview != null) ...[
                Text(
                  '${tr('Intended ticket recipient')}: ${state.preview!.recipientName}',
                ),
                Text(state.preview!.eventName),
                Text(
                  tr(
                    'A named ticket keeps its original learner. This transfer moves admission only and does not move wallet money.',
                  ),
                ),
                CheckboxListTile(
                  value: _confirmed,
                  onChanged: state.busy
                      ? null
                      : (v) => setState(() => _confirmed = v == true),
                  title: Text(
                    tr(
                      'I checked the original ticket and intended recipient. Transfer once.',
                    ),
                  ),
                ),
                FilledButton(
                  onPressed: !_confirmed || state.busy || cubit.hasPending
                      ? null
                      : () {
                          _confirmed = false;
                          _code.clear();
                          cubit.transfer();
                        },
                  child: Text(tr('Confirm original ticket transfer')),
                ),
                TextButton(
                  onPressed: state.busy
                      ? null
                      : () {
                          _code.clear();
                          _confirmed = false;
                          cubit.load();
                        },
                  child: Text(tr('Cancel ticket transfer review')),
                ),
              ],
              if (state.page!.page > 1)
                TextButton(
                  onPressed: state.busy
                      ? null
                      : () => cubit.load(page: state.page!.page - 1),
                  child: Text(tr('Previous tickets')),
                ),
              if (state.page!.page < state.page!.lastPage)
                TextButton(
                  onPressed: state.busy
                      ? null
                      : () => cubit.load(page: state.page!.page + 1),
                  child: Text(tr('More tickets')),
                ),
            ],
          ],
        ),
      );
    },
  );
}
