import 'package:eschool/data/models/leave.dart';
import 'package:eschool/data/repositories/leaveRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class LeaveState {}

class LeaveInitial extends LeaveState {}

class LeaveFetchSuccess extends LeaveState {
  LeaveFetchSuccess({
    required this.leaveList,
    required this.leavesTaken,
    required this.monthlyAllowedLeaves,
  });
  
  final List<Leave> leaveList;
  final double leavesTaken;
  final double monthlyAllowedLeaves;

  LeaveFetchSuccess copyWith({
    List<Leave>? leaveList,
    double? leavesTaken,
    double? monthlyAllowedLeaves,
  }) {
    return LeaveFetchSuccess(
      leaveList: leaveList ?? this.leaveList,
      leavesTaken: leavesTaken ?? this.leavesTaken,
      monthlyAllowedLeaves: monthlyAllowedLeaves ?? this.monthlyAllowedLeaves,
    );
  }

  double get remainingLeaves => monthlyAllowedLeaves - leavesTaken;
}

class LeaveFetchFailure extends LeaveState {

  LeaveFetchFailure(this.errorMessage);
  final String errorMessage;
}

class LeaveFetchInProgress extends LeaveState {}

class LeaveCubit extends Cubit<LeaveState> {

  LeaveCubit(this._leaveRepository) : super(LeaveInitial());
  final LeaveRepository _leaveRepository;

  void fetchLeaves({
    int? monthNumber,
    int? sessionYearId,
    required bool isParent,
    int? status,
    int? childId,
  }) {
    emit(LeaveFetchInProgress());
    _leaveRepository
        .getLeaves(
      status: status,
      month: monthNumber,
      sessionYearId: sessionYearId,
      isParent: isParent,
      childId: childId,
    )
        .then((leaveData) {
      emit(
        LeaveFetchSuccess(
          leaveList: leaveData.leaves,
          leavesTaken: leaveData.leavesTaken,
          monthlyAllowedLeaves: leaveData.monthlyAllowedLeaves,
        ),
      );
    }).catchError((error) {
      emit(LeaveFetchFailure(error.toString()));
    });
  }

  //to remove locally stored leave after successfully deleting it from server
  void removeLeave({required int leaveId}) {
    if (state is LeaveFetchSuccess) {
      final List<Leave> newLeaveList = [];
      newLeaveList.addAll((state as LeaveFetchSuccess).leaveList);
      final int indexOfLeaveToBeRemoved =
          newLeaveList.indexWhere((element) => element.id == leaveId);
      newLeaveList.removeAt(indexOfLeaveToBeRemoved);
      emit(
        (state as LeaveFetchSuccess).copyWith(
          leaveList: newLeaveList,
        ),
      );
    }
  }
}
