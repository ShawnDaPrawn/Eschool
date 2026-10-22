import 'package:equatable/equatable.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/my_school/my_school_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

enum MySchoolStatus { loading, unavailable, loaded, failure }

class MySchoolState extends Equatable {
  const MySchoolState({
    required this.status,
    required this.schoolId,
    this.info,
    this.error,
  });

  final MySchoolStatus status;

  /// The school shown.
  final int schoolId;
  final MySchoolInfo? info;
  final ApiException? error;

  @override
  List<Object?> get props => [status, schoolId, info, error];
}

/// My school: one school's contacts, documents and term dates. A parent
/// with children at two schools picks which one ([show]).
class MySchoolCubit extends Cubit<MySchoolState> {
  MySchoolCubit(this._repository, {required int schoolId})
    : super(MySchoolState(status: MySchoolStatus.loading, schoolId: schoolId));

  final MySchoolRepository _repository;

  Future<void> load() => show(state.schoolId);

  Future<void> show(int schoolId) async {
    emit(MySchoolState(status: MySchoolStatus.loading, schoolId: schoolId));
    try {
      final MySchoolInfo? info = await _repository.fetch(schoolId);
      if (isClosed || state.schoolId != schoolId) {
        return;
      }
      emit(
        MySchoolState(
          status: info == null
              ? MySchoolStatus.unavailable
              : MySchoolStatus.loaded,
          schoolId: schoolId,
          info: info,
        ),
      );
    } catch (e) {
      if (!isClosed && state.schoolId == schoolId) {
        emit(
          MySchoolState(
            status: MySchoolStatus.failure,
            schoolId: schoolId,
            error: ApiException.fromException(e),
          ),
        );
      }
    }
  }
}
