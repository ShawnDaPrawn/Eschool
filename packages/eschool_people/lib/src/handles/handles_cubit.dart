import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/handles/handles_repository.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

enum HandlesStatus { loading, loaded, unavailable, failure }

class HandlesState extends Equatable {
  const HandlesState({
    this.status = HandlesStatus.loading,
    this.value,
    this.requests = const [],
    this.busy = false,
    this.error,
    this.requestsError,
  });
  final HandlesStatus status;
  final HandleState? value;
  final List<GuardianHandleProposal> requests;
  final bool busy;
  final ApiException? error;
  final ApiException? requestsError;
  @override
  List<Object?> get props => [
    status,
    value,
    requests,
    busy,
    error,
    requestsError,
  ];
}

class HandlesCubit extends Cubit<HandlesState> {
  HandlesCubit(this.repository, {required this.guardian})
    : super(const HandlesState());
  final HandlesRepository repository;
  final bool guardian;

  Future<void> load() async {
    try {
      final value = await repository.fetch();
      if (isClosed) return;
      if (value == null) {
        emit(const HandlesState(status: HandlesStatus.unavailable));
        return;
      }
      List<GuardianHandleProposal> requests = [];
      ApiException? requestsError;
      if (guardian) {
        try {
          requests = await repository.proposals();
        } catch (error) {
          requestsError = ApiException.fromException(error);
        }
      }
      if (!isClosed) {
        emit(
          HandlesState(
            status: HandlesStatus.loaded,
            value: value,
            requests: requests,
            requestsError: requestsError,
          ),
        );
      }
    } catch (error) {
      if (!isClosed) {
        emit(
          HandlesState(
            status: HandlesStatus.failure,
            error: ApiException.fromException(error),
          ),
        );
      }
    }
  }

  Future<bool> choose(String handle) => _act(() async {
    await repository.choose(handle);
  });
  Future<bool> decide(int id, {required bool approve}) =>
      _act(() => repository.decide(id, approve: approve));

  Future<bool> _act(Future<void> Function() action) async {
    if (state.busy) return false;
    final kept = state;
    emit(
      HandlesState(
        status: kept.status,
        value: kept.value,
        requests: kept.requests,
        busy: true,
        requestsError: kept.requestsError,
      ),
    );
    try {
      await action();
      await load();
      return state.status == HandlesStatus.loaded;
    } catch (error) {
      if (!isClosed) {
        emit(
          HandlesState(
            status: kept.status,
            value: kept.value,
            requests: kept.requests,
            requestsError: kept.requestsError,
            error: ApiException.fromException(error),
          ),
        );
      }
      return false;
    }
  }
}
