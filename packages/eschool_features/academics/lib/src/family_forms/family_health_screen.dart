import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import '../localization/academic_text.dart';
import 'family_form_repository.dart';
import 'health_profile_adapter.dart';

/// Nested routes (including PDF/image viewers) belong to this private epoch.
class FamilyHealthScreen extends StatefulWidget {
  const FamilyHealthScreen({
    required this.repository,
    required this.child,
    required this.selectFile,
    super.key,
  });
  final FamilyFormRepository repository;
  final FamilyFormChoice child;
  final ProfileFileSelector selectFile;
  @override
  State<FamilyHealthScreen> createState() => _FamilyHealthScreenState();
}

class _FamilyHealthScreenState extends State<FamilyHealthScreen>
    with WidgetsBindingObserver {
  bool _foreground = true, _ready = false;
  String? _error;
  int _generation = 0;
  GlobalKey<NavigatorState> _navigator = GlobalKey();
  bool _active(int generation) =>
      mounted &&
      _foreground &&
      generation == _generation &&
      widget.repository.isActive;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  Future<void> _load() async {
    final generation = ++_generation;
    setState(() {
      _ready = false;
      _error = null;
      _navigator = GlobalKey();
    });
    try {
      final configuration = await widget.repository.configuration();
      if (!_active(generation)) return;
      if (!configuration.children.any(
        (child) =>
            child.id == widget.child.id &&
            child.personId == widget.child.personId,
      )) {
        throw ApiException(
          'Your family access changed. Reopen this screen.',
          statusCode: 403,
          isApiMessage: true,
        );
      }
      setState(() => _ready = true);
    } catch (error) {
      if (_active(generation)) {
        setState(
          () => _error = error is ApiException && error.isApiMessage
              ? error.errorMessage
              : 'The health form could not be loaded. Try again.',
        );
      }
    }
  }

  @override
  void didUpdateWidget(FamilyHealthScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository ||
        oldWidget.child.id != widget.child.id) {
      _load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _generation++;
      setState(() {
        _foreground = false;
        _ready = false;
        _error = null;
        _navigator = GlobalKey();
      });
    } else {
      _foreground = true;
      _load();
    }
  }

  @override
  void dispose() {
    _generation++;
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!_foreground || !_ready || !widget.repository.isActive) {
      return ScreenScaffold(
        title: academicText(context, 'Health forms'),
        body: Center(
          child: !_foreground
              ? const AcademicText('Reopen the health form to continue.')
              : _error == null
              ? const CircularProgressIndicator()
              : Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(_error!),
                    TextButton(
                      onPressed: _load,
                      child: const AcademicText('Refresh'),
                    ),
                  ],
                ),
        ),
      );
    }
    final generation = _generation;
    final adapter = HealthProfileAdapter(
      widget.repository,
      widget.child,
      active: () => _active(generation),
      onDenied: () {
        Future.microtask(() {
          if (!mounted || generation != _generation) return;
          setState(() {
            _generation++;
            _ready = false;
            _error = 'Your family access changed. Reopen this screen.';
            _navigator = GlobalKey();
          });
        });
      },
    );
    return NavigatorPopHandler<void>(
      onPopWithResult: (_) => _navigator.currentState?.maybePop(),
      child: Navigator(
        key: _navigator,
        onGenerateRoute: (_) => MaterialPageRoute<void>(
          builder: (_) => ProfileScreen.withRepository(
            adapter,
            subjects: [
              ProfileSubject(
                personId: widget.child.personId,
                schoolId: widget.repository.schoolId,
                profileType: 'learner',
                label: widget.child.name,
              ),
            ],
            titleKey: 'Health forms',
            selectFile: widget.selectFile,
            onExit: () {
              if (_active(generation)) Navigator.of(context).pop();
            },
          ),
        ),
      ),
    );
  }
}
