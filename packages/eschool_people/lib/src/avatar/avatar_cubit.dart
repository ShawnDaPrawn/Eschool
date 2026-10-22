import 'package:equatable/equatable.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/avatar/avatar_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

enum AvatarStatus { loading, unavailable, ready, failure }

class AvatarState extends Equatable {
  const AvatarState({
    required this.status,
    this.choice,
    this.saving = false,
    this.saved = false,
    this.error,
  });

  final AvatarStatus status;

  /// The current picture; `null` before one was chosen.
  final AvatarChoice? choice;
  final bool saving;

  /// The last change was saved (shown once).
  final bool saved;
  final ApiException? error;

  @override
  List<Object?> get props => [status, choice, saving, saved, error];
}

/// The avatar picker: the current picture, choosing one of the set, or
/// uploading a photo (which may wait for the school's approval).
class AvatarCubit extends Cubit<AvatarState> {
  AvatarCubit(this._repository)
    : super(const AvatarState(status: AvatarStatus.loading));

  final AvatarRepository _repository;

  Future<void> load() async {
    emit(const AvatarState(status: AvatarStatus.loading));
    try {
      final AvatarChoice? choice = await _repository.current();
      if (!isClosed) {
        emit(
          choice == null
              ? const AvatarState(status: AvatarStatus.unavailable)
              : AvatarState(status: AvatarStatus.ready, choice: choice),
        );
      }
    } catch (e) {
      if (!isClosed) {
        emit(
          AvatarState(
            status: AvatarStatus.failure,
            error: ApiException.fromException(e),
          ),
        );
      }
    }
  }

  Future<void> choose(String key) => _save(
    () => _repository.choose(key),
    unchangedWhen: state.choice?.key == key,
  );

  Future<void> upload(PickedPhoto photo) =>
      _save(() => _repository.upload(photo));

  Future<void> _save(
    Future<AvatarChoice> Function() send, {
    bool unchangedWhen = false,
  }) async {
    if (state.saving || state.status != AvatarStatus.ready || unchangedWhen) {
      return;
    }
    emit(
      AvatarState(
        status: AvatarStatus.ready,
        choice: state.choice,
        saving: true,
      ),
    );
    try {
      final AvatarChoice choice = await send();
      if (!isClosed) {
        emit(
          AvatarState(status: AvatarStatus.ready, choice: choice, saved: true),
        );
      }
    } catch (e) {
      if (!isClosed) {
        emit(
          AvatarState(
            status: AvatarStatus.ready,
            choice: state.choice,
            error: ApiException.fromException(e),
          ),
        );
      }
    }
  }
}
