// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool/data/models/fees.dart';
import 'package:eschool/data/repositories/studentRepository.dart';

abstract class StudentDetailedFeesState {}

class StudentDetailedFeesInitial extends StudentDetailedFeesState {}

class StudentDetailedFeesFetchSuccess extends StudentDetailedFeesState {
  StudentDetailedFeesFetchSuccess({required this.childFees});
  final ChildFees childFees;
}

class StudentDetailedFeesFetchFailure extends StudentDetailedFeesState {
  StudentDetailedFeesFetchFailure(this.errorMessage);
  final String errorMessage;
}

class StudentDetailedFeesFetchInProgress extends StudentDetailedFeesState {}

class StudentDetailedFeesCubit extends Cubit<StudentDetailedFeesState> {
  StudentDetailedFeesCubit(this._studentRepository)
    : super(StudentDetailedFeesInitial());
  final StudentRepository _studentRepository;

  void fetchDetailedFees({int? classSectionId, int? childId}) {
    emit(StudentDetailedFeesFetchInProgress());
    _studentRepository
        .fetchDetailedFees(childId: childId)
        .then(
          (value) => emit(StudentDetailedFeesFetchSuccess(childFees: value)),
        )
        .catchError((e) => emit(StudentDetailedFeesFetchFailure(e.toString())));
  }

  bool isTransactionPending() {
    if (state is StudentDetailedFeesFetchSuccess) {
      return (state as StudentDetailedFeesFetchSuccess)
          .childFees
          .isAnotherFeeTransactionPending;
    }
    return false;
  }
}
