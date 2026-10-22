import 'package:eschool/data/models/subject.dart';
import 'package:eschool/data/repositories/parentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class ChildSubjectsState {}

class ChildSubjectsInitial extends ChildSubjectsState {}

class ChildSubjectsFetchInProgress extends ChildSubjectsState {}

class ChildSubjectsFetchSuccess extends ChildSubjectsState {

  ChildSubjectsFetchSuccess({
    required this.coreSubjects,
    required this.electiveSubjects,
    required this.doesClassHaveElectiveSubjects,
  });
  final List<Subject> electiveSubjects;
  final List<Subject> coreSubjects;
  final bool doesClassHaveElectiveSubjects;
}

class ChildSubjectsFetchFailure extends ChildSubjectsState {

  ChildSubjectsFetchFailure(this.errorMessage);
  final String errorMessage;
}

class ChildSubjectsCubit extends Cubit<ChildSubjectsState> {

  ChildSubjectsCubit(this._parentRepository) : super(ChildSubjectsInitial());
  final ParentRepository _parentRepository;

  /// The child the subjects are (or are being fetched) for.
  int? _childId;
  int? get childId => _childId;

  /// Fetches [childId]'s subjects unless they are here or on their way: the
  /// app keeps one list, for the child picked in the child switcher.
  Future<void> ensureFor(int childId) async {
    if (_childId == childId &&
        (state is ChildSubjectsFetchSuccess ||
            state is ChildSubjectsFetchInProgress)) {
      return;
    }
    await fetchChildSubjects(childId);
  }

  Future<void> fetchChildSubjects(int childId) async {
    _childId = childId;
    emit(ChildSubjectsFetchInProgress());

    try {
      final result =
          await _parentRepository.fetchChildSubjects(childId: childId);
      //another child was picked while these were on their way
      if (isClosed || childId != _childId) {
        return;
      }
      emit(
        ChildSubjectsFetchSuccess(
          coreSubjects: result['coreSubjects'],
          electiveSubjects: result['electiveSubjects'],
          doesClassHaveElectiveSubjects:
              result['doesClassHaveElectiveSubjects'],
        ),
      );
    } catch (e) {
      if (!isClosed && childId == _childId) {
        emit(ChildSubjectsFetchFailure(e.toString()));
      }
    }
  }

  List<Subject> getSubjects() {
    if (state is ChildSubjectsFetchSuccess) {
      final List<Subject> subjects = [];

      subjects.addAll(
        (state as ChildSubjectsFetchSuccess)
            .coreSubjects
            .where((element) => element.id != 0)
            .toList(),
      );

      subjects.addAll(
        (state as ChildSubjectsFetchSuccess)
            .electiveSubjects
            .where((element) => element.id != 0)
            .toList(),
      );

      return subjects;
    }

    return [];
  }

  List<Subject> getSubjectsForAssignmentContainer() {
    return getSubjects()
      ..insert(
        0,
        Subject(
          bgColor: '',
          id: 0,
          code: '',
          image: '',
          mediumId: 1,
          name: '',
          type: '',
        ),
      );
  }
}
