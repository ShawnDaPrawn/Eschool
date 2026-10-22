import 'package:equatable/equatable.dart';
import 'package:eschool_core/eschool_core.dart';
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
  }) : _repository = repository,
       _cache = cache,
       _clock = clock ?? DateTime.now,
       super(const MeContextInitial());

  /// How old the context may get before coming back to the app fetches it
  /// again.
  static const Duration staleAfter = Duration(minutes: 5);

  final MeContextRepository _repository;
  final MeContextCache _cache;
  final DateTime Function() _clock;
  Future<void>? _loading;

  /// When the server last said it has no `/me/context` (it is older than
  /// API v2): it is not asked again on every resume.
  DateTime? _unavailableAt;

  MeContext? get context => state.context;

  /// When the context on show was fetched; `null` before the first answer.
  DateTime? get fetchedAt => _cache.fetchedAt();

  /// Fetches the context. A second call while one is on its way waits for
  /// it instead of asking twice.
  Future<void> load() =>
      _loading ??= _load().whenComplete(() => _loading = null);

  Future<void> _load() async {
    final MeContext? kept = state.context ?? _cache.read();
    emit(MeContextLoading(context: kept));
    try {
      final MeContext? fresh = await _repository.fetchContext();
      if (isClosed) {
        return;
      }
      if (fresh == null) {
        await _cache.clear();
        _unavailableAt = _clock();
        emit(const MeContextUnavailable());
        return;
      }
      await _cache.write(fresh, fetchedAt: _clock());
      emit(MeContextLoaded(fresh));
    } catch (e) {
      if (!isClosed) {
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

  Future<void> clear() async {
    await _cache.clear();
    emit(const MeContextInitial());
  }
}
