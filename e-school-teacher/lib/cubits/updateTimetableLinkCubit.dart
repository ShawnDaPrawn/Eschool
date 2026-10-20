import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class UpdateTimetableLinkState {}

class UpdateTimetableLinkInitial extends UpdateTimetableLinkState {}

class UpdateTimetableLinkInProgress extends UpdateTimetableLinkState {}

class UpdateTimetableLinkSuccess extends UpdateTimetableLinkState {
  UpdateTimetableLinkSuccess({required this.url, required this.name});
  final String? url;
  final String? name;
}

class UpdateTimetableLinkFailure extends UpdateTimetableLinkState {
  UpdateTimetableLinkFailure(this.exception);
  final ApiException exception;
}

class UpdateTimetableLinkCubit extends Cubit<UpdateTimetableLinkState> {
  UpdateTimetableLinkCubit(this._teacherRepository)
    : super(UpdateTimetableLinkInitial());
  final TeacherRepository _teacherRepository;

  Future<void> updateTimetableLink({
    required String timetableSlotId,
    required String? url,
    required String? name,
  }) async {
    emit(UpdateTimetableLinkInProgress());
    try {
      await _teacherRepository.updateTimeTableLink(
        timetableSlotId: timetableSlotId,
        linkCustomUrl: url,
        linkName: name,
      );

      emit(UpdateTimetableLinkSuccess(url: url, name: name));
    } catch (e) {
      emit(UpdateTimetableLinkFailure(ApiException.fromException(e)));
    }
  }
}
