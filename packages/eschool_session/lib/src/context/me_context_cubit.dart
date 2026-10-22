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

/// Holds the signed-in user's [MeContext]. The apps call [load] on launch
/// when someone is signed in, after sign-in and after a school switch, and
/// [clear] on sign-out.
class MeContextCubit extends Cubit<MeContextState> {
  MeContextCubit({
    required MeContextRepository repository,
    required MeContextCache cache,
  }) : _repository = repository,
       _cache = cache,
       super(const MeContextInitial());

  final MeContextRepository _repository;
  final MeContextCache _cache;

  MeContext? get context => state.context;

  Future<void> load() async {
    final MeContext? kept = state.context ?? _cache.read();
    emit(MeContextLoading(context: kept));
    try {
      final MeContext? fresh = await _repository.fetchContext();
      if (isClosed) {
        return;
      }
      if (fresh == null) {
        await _cache.clear();
        emit(const MeContextUnavailable());
        return;
      }
      await _cache.write(fresh);
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

  Future<void> clear() async {
    await _cache.clear();
    emit(const MeContextInitial());
  }
}
