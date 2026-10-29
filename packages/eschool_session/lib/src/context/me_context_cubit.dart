import 'package:equatable/equatable.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/src/auth/session_identity.dart';
import 'package:eschool_session/src/context/me_context.dart';
import 'package:eschool_session/src/context/me_context_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

sealed class MeContextState extends Equatable {
  const MeContextState();

  /// The context to use now: the server's latest answer, or the one kept
  /// from last time while loading or offline. `null` when there is none.
  MeContext? get context => null;

  @override
  List<Object?> get props => [context];
}

/// Nobody is signed in, or nothing was asked yet.
class MeContextInitial extends MeContextState {
  const MeContextInitial();
}

class MeContextLoading extends MeContextState {
  const MeContextLoading({this.context});

  @override
  final MeContext? context;
}

class MeContextLoaded extends MeContextState {
  const MeContextLoaded(this.context);

  @override
  final MeContext context;
}

/// The server has no `/api/v2/me/context` (it is older than API v2). The app
/// carries on exactly as it did before the endpoint existed.
class MeContextUnavailable extends MeContextState {
  const MeContextUnavailable();
}

/// The call failed (no network, server error). [context] is the one kept
/// from last time, if any.
class MeContextFailure extends MeContextState {
  const MeContextFailure(this.errorCode, {this.context});

  final String errorCode;

  @override
  final MeContext? context;

  @override
  List<Object?> get props => [errorCode, context];
}

/// Holds the signed-in user's [MeContext]. The apps call [load] at start-up
/// when someone is already signed in, after signing in, after a school
/// switch and when a `context.changed` push arrives; [refreshIfStale] when
/// the app comes back to the front; and [clear] on sign-out.
class MeContextCubit extends Cubit<MeContextState> {
  MeContextCubit({
    required MeContextRepository repository,
    required MeContextCache cache,
    DateTime Function()? clock,
    Object? Function()? contextIdentity,
  }) : _repository = repository,
       _cache = cache,
       _clock = clock ?? DateTime.now,
       _contextIdentity = contextIdentity,
       _lastIdentity = contextIdentity?.call(),
       super(const MeContextInitial());

  /// How old the context may get before coming back to the app fetches it
  /// again.
  static const Duration staleAfter = Duration(minutes: 5);

  final MeContextRepository _repository;
  final MeContextCache _cache;
  final DateTime Function() _clock;
  Future<void>? _loading;
  final Object? Function()? _contextIdentity;
  Object? _lastIdentity;
  int _generation = 0;
  bool _discardCache = false;
  Future<void>? _cacheWork;

  bool _sameIdentity(Object? previous, Object? current, MeContext? known) =>
      previous == current ||
      (previous is SessionIdentity &&
          current is SessionIdentity &&
          previous.resolvesInitialSchool(current, known?.currentSchoolId));

  Object? _readIdentity() {
    final current = _contextIdentity?.call();
    if (_sameIdentity(_lastIdentity, current, state.context)) {
      _lastIdentity = current;
    }
    return current;
  }

  bool _current(int generation, Object? identity, [MeContext? fresh]) =>
      !isClosed &&
      generation == _generation &&
      _sameIdentity(identity, _contextIdentity?.call(), fresh ?? state.context);

  Future<void> _cacheMutation(Future<void> Function() work) {
    final previous = _cacheWork;
    final result = previous == null
        ? Future<void>.sync(work)
        : previous.then((_) => work());
    late Future<void> barrier;
    barrier = result
        .then<void>((_) {}, onError: (Object _, StackTrace _) {})
        .whenComplete(() {
          if (identical(_cacheWork, barrier)) _cacheWork = null;
        });
    _cacheWork = barrier;
    return result;
  }

  /// When the server last said it has no `/me/context` (it is older than
  /// API v2): it is not asked again on every resume.
  DateTime? _unavailableAt;

  MeContext? get context => state.context;

  /// When the context on show was fetched; `null` before the first answer.
  DateTime? get fetchedAt => _cache.fetchedAt();

  /// Fetches the context. A second call while one is on its way waits for
  /// it instead of asking twice.
  Future<void> load() {
    if (isClosed) return Future<void>.value();
    final identity = _readIdentity();
    if (_loading != null && identity == _lastIdentity) return _loading!;
    if (identity != _lastIdentity) {
      _discardCache = true;
      _unavailableAt = null;
      // A write already in progress must finish before this erasure. Otherwise
      // a failed replacement fetch can leave the old school's disk cache behind.
      _cacheMutation(_cache.clear).ignore();
    }
    _lastIdentity = identity;
    final generation = ++_generation;
    final kept = _discardCache ? null : (state.context ?? _cache.read());
    emit(MeContextLoading(context: kept));
    return _loading = _load(generation, identity, kept).whenComplete(() {
      if (generation == _generation) _loading = null;
    });
  }

  Future<void> _load(int generation, Object? identity, MeContext? kept) async {
    try {
      final pendingCache = _cacheWork;
      if (pendingCache != null) await pendingCache;
      if (!_current(generation, identity)) return;
      final fresh = await _repository.fetchContext();
      if (!_current(generation, identity, fresh)) return;
      await _cacheMutation(() async {
        if (!_current(generation, identity, fresh)) return;
        if (fresh == null) {
          await _cache.clear();
        } else {
          await _cache.write(fresh, fetchedAt: _clock());
        }
      });
      if (!_current(generation, identity, fresh)) return;
      if (fresh == null) {
        _unavailableAt = _clock();
        _discardCache = true;
        emit(const MeContextUnavailable());
      } else {
        _discardCache = false;
        emit(MeContextLoaded(fresh));
      }
    } catch (e) {
      if (_current(generation, identity)) {
        emit(
          MeContextFailure(
            ApiException.fromException(e).errorMessage,
            context: kept,
          ),
        );
      }
    }
  }

  /// Fetches the context again when the one kept is older than [maxAge]
  /// (or there is none, or the last try failed). Answers whether it did.
  Future<bool> refreshIfStale({Duration maxAge = staleAfter}) async {
    if (_readIdentity() != _lastIdentity) {
      await load();
      return true;
    }
    final DateTime? at = state is MeContextUnavailable
        ? _unavailableAt
        : _cache.fetchedAt();
    final bool fresh =
        at != null &&
        state is! MeContextFailure &&
        _clock().difference(at) <= maxAge;
    if (fresh) {
      return false;
    }
    await load();
    return true;
  }

  Future<void> clear() {
    _generation++;
    _loading = null;
    _discardCache = true;
    _unavailableAt = null;
    if (!isClosed) emit(const MeContextInitial());
    return _cacheMutation(_cache.clear);
  }

  @override
  Future<void> close() {
    _generation++;
    _loading = null;
    return super.close();
  }
}
