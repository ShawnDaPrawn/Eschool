import 'package:equatable/equatable.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class SelectElectiveSubjectsState extends Equatable {}

class SelectElectiveSubjectsInitial extends SelectElectiveSubjectsState {
  @override
  List<Object?> get props => [];
}

class SelectElectiveSubjectsInProgress extends SelectElectiveSubjectsState {
  @override
  List<Object?> get props => [];
}

class SelectElectiveSubjectsSuccess extends SelectElectiveSubjectsState {
  SelectElectiveSubjectsSuccess(this.electedSubjectIds);
  final List<int> electedSubjectIds;

  @override
  List<Object?> get props => [electedSubjectIds];
}

class SelectElectiveSubjectsFailure extends SelectElectiveSubjectsState {
  SelectElectiveSubjectsFailure(this.exception);
  final ApiException exception;

  @override
  List<Object?> get props => [exception];
}

class SelectElectiveSubjectsCubit extends Cubit<SelectElectiveSubjectsState> {
  SelectElectiveSubjectsCubit(this._studentRepository)
    : super(SelectElectiveSubjectsInitial());
  final StudentRepository _studentRepository;

  List<int> _getElectedSubjectIds(Map<int, List<int>> electedSubjectGroups) {
    final List<int> subjectIds = [];

    for (final key in electedSubjectGroups.keys) {
      subjectIds.addAll(electedSubjectGroups[key]!.toList());
    }

    return subjectIds;
  }

  void selectElectiveSubjects({
    required Map<int, List<int>> electedSubjectGroups,
  }) {
    emit(SelectElectiveSubjectsInProgress());
    _studentRepository
        .selectElectiveSubjects(electedSubjectGroups: electedSubjectGroups)
        .then((value) {
          return emit(
            SelectElectiveSubjectsSuccess(
              _getElectedSubjectIds(electedSubjectGroups),
            ),
          );
        })
        .catchError(
          (e) => emit(
            SelectElectiveSubjectsFailure(ApiException.fromException(e)),
          ),
        );
  }
}
