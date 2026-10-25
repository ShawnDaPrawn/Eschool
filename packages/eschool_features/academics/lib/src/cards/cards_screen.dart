import 'dart:async';
import 'dart:typed_data';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import '../wellbeing/report_photo_picker.dart';
import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:uuid/uuid.dart';
import '../gallery/private_gallery_image.dart';
import '../localization/academic_text.dart';
import 'card_repository.dart';

class CardPersonChoice {
  const CardPersonChoice(this.id, this.name, this.type);
  final int id;
  final String name, type;
}

class _PortraitDraft {
  _PortraitDraft(this.person, this.bytes) : operation = const Uuid().v4();
  final CardPersonChoice person;
  final Uint8List bytes;
  final String operation;
  void clear() => bytes.fillRange(0, bytes.length, 0);
}

/// Card proofs and pixels live only inside one fresh authorization epoch.
class SchoolCardsScreen extends StatefulWidget {
  const SchoolCardsScreen({
    required this.repository,
    required this.people,
    required this.schoolName,
    this.selfOnly = false,
    this.selectPhoto,
    this.monotonicNow,
    super.key,
  });
  final Future<Uint8List?> Function()? selectPhoto;
  final Duration Function()? monotonicNow;
  final bool selfOnly;
  final CardRepository repository;
  final List<CardPersonChoice> people;
  final String schoolName;
  @override
  State<SchoolCardsScreen> createState() => _SchoolCardsScreenState();
}

class _SchoolCardsScreenState extends State<SchoolCardsScreen>
    with WidgetsBindingObserver {
  List<SecureCard> _cards = [];
  SecureCardConfiguration? _configuration;
  SecureCard? _selected;
  String? _proof, _error;
  bool _foreground = true,
      _loading = false,
      _proofBusy = false,
      _saving = false;
  int _generation = 0, _photoEpoch = 0;
  int? _nextPage;
  int _proofSeconds = 0;
  final Stopwatch _monotonic = Stopwatch()..start();
  Duration _now() => widget.monotonicNow?.call() ?? _monotonic.elapsed;
  Timer? _timer;
  _PortraitDraft? _portrait;
  TextEditingController? _reasonController;
  BuildContext? _dialogContext;
  (String, String, String)? _freeze;

  bool _active(int generation) =>
      mounted &&
      _foreground &&
      generation == _generation &&
      widget.repository.isActive;
  String l(String value) => academicText(context, value);
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void didUpdateWidget(SchoolCardsScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository) {
      _generation++;
      _closeDialog();
      _erase();
      _clearPending();
      _load();
    }
  }

  void _clearProof() {
    _timer?.cancel();
    _timer = null;
    _proof = null;
    _proofSeconds = 0;
    _proofBusy = false;
  }

  void _closeDialog() {
    _reasonController?.clear();
    final dialog = _dialogContext;
    _dialogContext = null;
    if (dialog != null &&
        dialog.mounted &&
        ModalRoute.of(dialog)?.isCurrent == true) {
      Navigator.of(dialog).pop<String>();
    }
  }

  void _erase() {
    _loading = false;
    _clearProof();
    _selected = null;
    _cards = [];
    _configuration = null;
    _error = null;
    _nextPage = null;
  }

  void _clearPending() {
    _portrait?.clear();
    _portrait = null;
    _freeze = null;
    _saving = false;
  }

  void _deny() {
    if (!mounted) return;
    setState(() {
      _generation++;
      _closeDialog();
      _erase();
      _clearPending();
      _error = 'Your card access changed. Reopen this screen.';
    });
  }

  void _failure(Object error) {
    if (error is ApiException &&
        const [401, 403, 404, 423].contains(error.statusCode)) {
      _deny();
      return;
    }
    setState(
      () => _error = 'The card request could not be completed. Try again.',
    );
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      setState(() {
        _closeDialog();
        _foreground = false;
        _generation++;
        _erase();
        _loading = false;
        _saving = false;
      });
    } else {
      setState(() {
        _foreground = true;
        _generation++;
      });
      _load();
    }
  }

  Future<void> _load({int page = 1}) async {
    final generation = _generation;
    if (!_active(generation)) {
      if (mounted && _foreground) _deny();
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final config = await widget.repository.configuration();
      final cards = await widget.repository.mine(page: page);
      if (!_active(generation)) return;
      final visible = widget.selfOnly
          ? cards.items
                .where((c) => c.personId == widget.repository.accountId)
                .toList()
          : cards.items;
      final known = widget.people.map((p) => p.id).toSet();
      if (visible.any((c) => !known.contains(c.personId))) {
        _deny();
        return;
      }
      setState(() {
        _configuration = config;
        _nextPage = cards.nextPage;
        _cards = page == 1 ? visible : [..._cards, ...visible];
        if (_selected != null &&
            (!_cards.any(
                  (c) =>
                      c.id == _selected!.id &&
                      c.revision == _selected!.revision &&
                      c.status == 'active',
                ) ||
                !config.writable)) {
          _clearProof();
          _selected = null;
        }
        final pending = _freeze;
        if (pending != null &&
            _cards.any((c) => c.id == pending.$1 && c.status != 'active')) {
          _freeze = null;
        }
      });
    } catch (error) {
      if (_active(generation)) _failure(error);
    } finally {
      if (_active(generation)) setState(() => _loading = false);
    }
  }

  Future<void> _code(SecureCard card) async {
    final generation = _generation;
    if (!_active(generation) || _proofBusy || _saving) return;
    setState(() {
      _selected = card;
      _proofBusy = true;
      _error = null;
    });
    final started = _now();
    try {
      final proof = await widget.repository.credential(card.id);
      if (!_active(generation) || _selected?.id != card.id) return;
      // Subtract the entire request time: never extend the signed server lease
      // because of a skewed local wall clock or a delayed response.
      final lifetime = const Duration(seconds: 25) - (_now() - started);
      if (lifetime <= Duration.zero) {
        setState(_clearProof);
        return;
      }
      final received = _now();
      setState(() {
        _selected = proof.card;
        _proof = proof.credential;
        _proofSeconds = lifetime.inSeconds;
      });
      _timer?.cancel();
      _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
        if (!_active(generation) || _selected?.id != card.id) {
          timer.cancel();
          return;
        }
        final left = lifetime - (_now() - received);
        if (left <= Duration.zero) {
          setState(() {
            _proof = null;
            _proofSeconds = 0;
          });
        } else {
          setState(() => _proofSeconds = left.inSeconds);
        }
        if (_now() - received >= const Duration(seconds: 19) &&
            !_proofBusy &&
            !_saving) {
          _code(proof.card);
        }
      });
    } catch (error) {
      if (_active(generation)) {
        setState(_clearProof);
        _failure(error);
      }
    } finally {
      if (_active(generation)) setState(() => _proofBusy = false);
    }
  }

  Future<void> _askFreeze(SecureCard card) async {
    final generation = _generation;
    final value = await showDialog<String>(
      context: context,
      builder: (dialog) => _FreezeCardDialog(
        ready: (controller, context) {
          _reasonController = controller;
          _dialogContext = context;
        },
        closed: () {
          _reasonController = null;
          _dialogContext = null;
        },
      ),
    );
    _dialogContext = null;
    _reasonController = null;
    if (!_active(generation) || value == null || value.isEmpty) return;
    setState(() {
      _clearProof();
      _freeze = (card.id, const Uuid().v4(), value);
    });
    await _sendFreeze();
  }

  Future<void> _sendFreeze() async {
    final input = _freeze, generation = _generation;
    if (input == null || !_active(generation) || _saving) return;
    setState(() {
      _saving = true;
      _error = null;
      _clearProof();
    });
    try {
      await widget.repository.freeze(input.$1, input.$2, input.$3);
      if (!_active(generation)) return;
      setState(() {
        _freeze = null;
        _selected = null;
      });
      await _load();
    } catch (error) {
      if (_active(generation)) _failure(error);
    } finally {
      if (_active(generation)) setState(() => _saving = false);
    }
  }

  Future<void> _choosePortrait(CardPersonChoice person) async {
    if (_portrait != null || _saving) return;
    final repository = widget.repository;
    try {
      final bytes = await (widget.selectPhoto ?? selectReportPhoto)();
      if (!mounted ||
          !identical(repository, widget.repository) ||
          !_active(_generation) ||
          bytes == null) {
        return;
      }
      await _load();
      if (!mounted ||
          !identical(repository, widget.repository) ||
          !_active(_generation) ||
          _configuration?.writable != true ||
          !widget.people.any(
            (p) => p.id == person.id && p.type == person.type,
          )) {
        return;
      }
      if (bytes.isEmpty || bytes.length > 4194304) {
        setState(() => _error = 'Choose a JPEG or PNG up to 4 MB.');
        return;
      }
      setState(() {
        _clearProof();
        _portrait = _PortraitDraft(person, Uint8List.fromList(bytes));
      });
      await _sendPortrait();
    } catch (error) {
      if (mounted &&
          identical(repository, widget.repository) &&
          _active(_generation)) {
        _failure(error);
      }
    }
  }

  Future<void> _sendPortrait() async {
    final pending = _portrait, generation = _generation;
    if (pending == null || !_active(generation) || _saving) return;
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      await widget.repository.upload(
        pending.person.id,
        pending.person.type,
        pending.operation,
        pending.bytes,
      );
      if (!_active(generation)) return;
      setState(() {
        _portrait?.clear();
        _portrait = null;
        _photoEpoch++;
      });
      await _load();
    } catch (error) {
      if (_active(generation)) _failure(error);
    } finally {
      if (_active(generation)) setState(() => _saving = false);
    }
  }

  @override
  void dispose() {
    _generation++;
    _reasonController?.clear();
    WidgetsBinding.instance.removeObserver(this);
    _erase();
    _clearPending();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final generation = _generation;
    return ScreenScaffold(
      title: l('School identity cards'),
      body: !_foreground
          ? const SizedBox.shrink()
          : !_active(generation)
          ? Center(
              child: Text(l('Your card access changed. Reopen this screen.')),
            )
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (_loading || _saving) const LinearProgressIndicator(),
                if (_error != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Text(
                      l(_error!),
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  ),
                if (_freeze != null)
                  FilledButton(
                    onPressed: _saving ? null : _sendFreeze,
                    child: Text(l('Retry card freeze')),
                  ),
                if (_portrait != null)
                  FilledButton(
                    onPressed: _saving ? null : _sendPortrait,
                    child: Text(l('Retry portrait save')),
                  ),
                if (_configuration != null && _cards.isEmpty && !_loading)
                  Text(
                    l(
                      'Your school has not issued a card yet. You can add a private portrait first.',
                    ),
                  ),
                for (final card in _cards)
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            widget.schoolName,
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                          Text(
                            widget.people
                                .firstWhere((p) => p.id == card.personId)
                                .name,
                            style: Theme.of(context).textTheme.headlineSmall,
                          ),
                          Text(
                            l(switch (card.type) {
                              'staff' => 'Staff',
                              'guardian' => 'Guardian',
                              _ => 'Learner',
                            }),
                          ),
                          Text(
                            l(switch (card.status) {
                              'frozen' => 'Frozen',
                              'replaced' => 'Replaced',
                              _ => 'Active',
                            }),
                          ),
                          SizedBox(
                            width: 200,
                            child: PrivateGalleryImage(
                              key: ValueKey(
                                '${card.id}:$_photoEpoch:$generation',
                              ),
                              identity: '${card.id}:$_photoEpoch:$generation',
                              load: () => widget.repository.portrait(card.id),
                              active: () => _active(generation),
                              label: l('Private card portrait'),
                              onDenied: _deny,
                            ),
                          ),
                          if (card.status == 'active')
                            Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: [
                                FilledButton(
                                  onPressed:
                                      _configuration?.writable != true ||
                                          _saving ||
                                          _proofBusy
                                      ? null
                                      : () {
                                          _clearProof();
                                          _code(card);
                                        },
                                  child: Text(l('Show secure code')),
                                ),
                                OutlinedButton(
                                  onPressed: _saving
                                      ? null
                                      : () => _askFreeze(card),
                                  child: Text(l('Freeze card')),
                                ),
                              ],
                            )
                          else
                            Text(
                              l(
                                'Contact your school to restore or replace this card.',
                              ),
                            ),
                          if (_selected?.id == card.id && _proof != null)
                            Column(
                              children: [
                                Semantics(
                                  label: l('Short-lived school card code'),
                                  child: ColoredBox(
                                    color: Colors.white,
                                    child: Padding(
                                      padding: const EdgeInsets.all(12),
                                      child: QrImageView(
                                        key: ValueKey(
                                          'secure-card-code-${card.id}',
                                        ),
                                        data: _proof!,
                                        size: 220,
                                        version: QrVersions.auto,
                                        errorCorrectionLevel:
                                            QrErrorCorrectLevel.M,
                                      ),
                                    ),
                                  ),
                                ),
                                Text(
                                  '${l('Secure code expires in')} $_proofSeconds ${l('seconds')}',
                                ),
                              ],
                            ),
                          if (_proofBusy && _selected?.id == card.id)
                            const LinearProgressIndicator(),
                        ],
                      ),
                    ),
                  ),
                if (_nextPage != null)
                  TextButton(
                    onPressed: _loading ? null : () => _load(page: _nextPage!),
                    child: Text(l('Load more')),
                  ),
                if (_configuration?.writable == true && _portrait == null) ...[
                  const SizedBox(height: 20),
                  Text(
                    l('Private card portraits'),
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  for (final person in widget.people)
                    ListTile(
                      title: Text(person.name),
                      trailing: const Icon(Icons.add_a_photo_outlined),
                      onTap: _saving ? null : () => _choosePortrait(person),
                    ),
                ],
                TextButton(
                  onPressed: _loading || _saving ? null : _load,
                  child: Text(l('Refresh')),
                ),
              ],
            ),
    );
  }
}

class _FreezeCardDialog extends StatefulWidget {
  const _FreezeCardDialog({required this.ready, required this.closed});
  final void Function(TextEditingController, BuildContext) ready;
  final VoidCallback closed;
  @override
  State<_FreezeCardDialog> createState() => _FreezeCardDialogState();
}

class _FreezeCardDialogState extends State<_FreezeCardDialog> {
  final reason = TextEditingController();
  @override
  void dispose() {
    widget.closed();
    reason.clear();
    reason.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    widget.ready(reason, context);
    String l(String value) => academicText(context, value);
    return AlertDialog(
      title: Text(l('Freeze this card?')),
      content: SizedBox(
        width: 400,
        child: TextField(
          controller: reason,
          maxLength: 2000,
          maxLines: 3,
          onChanged: (_) => setState(() {}),
          decoration: InputDecoration(labelText: l('Reason')),
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text(l('Cancel')),
        ),
        FilledButton(
          onPressed: reason.text.trim().isEmpty
              ? null
              : () {
                  final value = reason.text.trim();
                  reason.clear();
                  Navigator.pop(context, value);
                },
          child: Text(l('Freeze card')),
        ),
      ],
    );
  }
}
