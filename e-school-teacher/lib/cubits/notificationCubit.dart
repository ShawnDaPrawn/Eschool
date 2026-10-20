import 'package:eschool_teacher/data/models/customNotification.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class NotificationState {}

class NotificationInitial extends NotificationState {}

class NotificationFetchInProgress extends NotificationState {}

class NotificationFetchFailure extends NotificationState {

  NotificationFetchFailure({required this.errorMessage, this.page});
  final String errorMessage;
  final int? page;
}

class NotificationFetchSuccess extends NotificationState {

  NotificationFetchSuccess({
    required this.notifications,
    required this.totalPage,
    required this.currentPage,
    required this.moreNotificationsFetchError,
    required this.moreNotificationsFetchInProgress,
  });
  final List<CustomNotification> notifications;
  final int totalPage;
  final int currentPage;
  final bool moreNotificationsFetchError;
  final bool moreNotificationsFetchInProgress;

  NotificationFetchSuccess copyWith({
    List<CustomNotification>? newnotifications,
    int? newTotalPage,
    int? newCurrentPage,
    bool? newFetchMorenotificationsInProgress,
    bool? newFetchMorenotificationsError,
  }) {
    return NotificationFetchSuccess(
      notifications: newnotifications ?? notifications,
      totalPage: newTotalPage ?? totalPage,
      currentPage: newCurrentPage ?? currentPage,
      moreNotificationsFetchInProgress: newFetchMorenotificationsInProgress ??
          moreNotificationsFetchInProgress,
      moreNotificationsFetchError:
          newFetchMorenotificationsError ?? moreNotificationsFetchError,
    );
  }
}

class NotificationsCubit extends Cubit<NotificationState> {

  NotificationsCubit(this._teacherRepository) : super(NotificationInitial());
  final TeacherRepository _teacherRepository;

  bool isLoading() {
    if (state is NotificationFetchInProgress) {
      return true;
    }
    return false;
  }

  Future<void> fetchNotifications({
    required int page,
  }) async {
    emit(NotificationFetchInProgress());
    try {
      final value = await _teacherRepository.fetchNotifications(
        page: page,
      );

      emit(
        NotificationFetchSuccess(
          notifications: value['notifications'],
          totalPage: value['totalPage'],
          currentPage: value['currentPage'],
          moreNotificationsFetchError: false,
          moreNotificationsFetchInProgress: false,
        ),
      );
    } catch (e) {
      emit(
        NotificationFetchFailure(
          errorMessage: e.toString(),
          page: page,
        ),
      );
    }
  }

  Future<void> fetchMoreNotifications() async {
    if (state is NotificationFetchSuccess) {
      final stateAs = state as NotificationFetchSuccess;
      if (stateAs.moreNotificationsFetchInProgress) {
        return;
      }
      try {
        emit(stateAs.copyWith(newFetchMorenotificationsInProgress: true));

        final moreTransactionResult =
            await _teacherRepository.fetchNotifications(
          page: stateAs.currentPage + 1,
        );

        final List<CustomNotification> notifications = stateAs.notifications;

        notifications.addAll(moreTransactionResult['notifications']);

        emit(
          NotificationFetchSuccess(
            notifications: notifications,
            totalPage: moreTransactionResult['totalPage'],
            currentPage: moreTransactionResult['currentPage'],
            moreNotificationsFetchError: false,
            moreNotificationsFetchInProgress: false,
          ),
        );
      } catch (e) {
        emit(
          (state as NotificationFetchSuccess).copyWith(
            newFetchMorenotificationsInProgress: false,
            newFetchMorenotificationsError: true,
          ),
        );
      }
    }
  }

  bool hasMore() {
    if (state is NotificationFetchSuccess) {
      return (state as NotificationFetchSuccess).currentPage <
          (state as NotificationFetchSuccess).totalPage;
    }
    return false;
  }
}
