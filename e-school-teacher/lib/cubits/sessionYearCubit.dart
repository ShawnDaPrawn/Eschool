import 'package:eschool_teacher/data/models/sessionYear.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class SessionYearState {}

class SessionYearInitial extends SessionYearState {}

class SessionYearFetchSuccess extends SessionYearState {

  SessionYearFetchSuccess({required this.sessionYearList});
  final List<SessionYear> sessionYearList;
}

class SessionYearFetchFailure extends SessionYearState {

  SessionYearFetchFailure(this.errorMessage);
  final String errorMessage;
}

class SessionYearFetchInProgress extends SessionYearState {}

class SessionYearCubit extends Cubit<SessionYearState> {

  SessionYearCubit(this._systemRepository) : super(SessionYearInitial());
  final SystemRepository _systemRepository;

  Future<void> fetchSessionYears() async {
    emit(SessionYearFetchInProgress());
    try {
      emit(
        SessionYearFetchSuccess(
          sessionYearList: await _systemRepository.fetchSessionYears(),
        ),
      );
    } catch (e) {
      emit(SessionYearFetchFailure(e.toString()));
    }
  }
}
