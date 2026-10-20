import 'package:eschool/data/models/announcement.dart';
import 'package:eschool/data/repositories/announcementRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class SubjectAnnouncementState {}

class SubjectAnnouncementInitial extends SubjectAnnouncementState {}

class SubjectAnnouncementFetchInProgress extends SubjectAnnouncementState {}

class SubjectAnnouncementFetchSuccess extends SubjectAnnouncementState {

  SubjectAnnouncementFetchSuccess({
    required this.announcements,
    required this.fetchMoreAnnouncementsInProgress,
    required this.moreAnnouncementsFetchError,
    required this.currentPage,
    required this.totalPage,
  });
  final List<Announcement> announcements;
  final int totalPage;
  final int currentPage;
  final bool moreAnnouncementsFetchError;
  final bool fetchMoreAnnouncementsInProgress;

  SubjectAnnouncementFetchSuccess copyWith({
    List<Announcement>? newAnnouncements,
    bool? newFetchMoreAnnouncementsInProgress,
    bool? newMoreAnnouncementsFetchError,
    int? newCurrentPage,
    int? newTotalPage,
  }) {
    return SubjectAnnouncementFetchSuccess(
      announcements: newAnnouncements ?? announcements,
      fetchMoreAnnouncementsInProgress: newFetchMoreAnnouncementsInProgress ??
          fetchMoreAnnouncementsInProgress,
      moreAnnouncementsFetchError:
          newMoreAnnouncementsFetchError ?? moreAnnouncementsFetchError,
      currentPage: newCurrentPage ?? currentPage,
      totalPage: newTotalPage ?? totalPage,
    );
  }
}

class SubjectAnnouncementFetchFailure extends SubjectAnnouncementState {

  SubjectAnnouncementFetchFailure({required this.errorMessage});
  final String errorMessage;
}

class SubjectAnnouncementCubit extends Cubit<SubjectAnnouncementState> {

  SubjectAnnouncementCubit(this._announcementRepository)
      : super(SubjectAnnouncementInitial());
  final AnnouncementRepository _announcementRepository;

  Future<void> fetchSubjectAnnouncement({
    required bool useParentApi,
    required int subjectId, int? childId,
  }) async {
    try {
      emit(SubjectAnnouncementFetchInProgress());
      final result = await _announcementRepository.fetchAnnouncements(
        isGeneralAnnouncement: false,
        childId: childId ?? 0,
        subjectId: subjectId,
        useParentApi: useParentApi,
      );
      emit(
        SubjectAnnouncementFetchSuccess(
          announcements: result['announcements'],
          fetchMoreAnnouncementsInProgress: false,
          moreAnnouncementsFetchError: false,
          currentPage: result['currentPage'],
          totalPage: result['totalPage'],
        ),
      );
    } catch (e) {
      emit(SubjectAnnouncementFetchFailure(errorMessage: e.toString()));
    }
  }

  bool hasMore() {
    if (state is SubjectAnnouncementFetchSuccess) {
      return (state as SubjectAnnouncementFetchSuccess).currentPage <
          (state as SubjectAnnouncementFetchSuccess).totalPage;
    }
    return false;
  }

  Future<void> fetchMoreAnnouncements({
    required bool useParentApi,
    required int subjectId, int? childId,
  }) async {
    if (state is SubjectAnnouncementFetchSuccess) {
      if ((state as SubjectAnnouncementFetchSuccess)
          .fetchMoreAnnouncementsInProgress) {
        return;
      }
      try {
        emit(
          (state as SubjectAnnouncementFetchSuccess)
              .copyWith(newFetchMoreAnnouncementsInProgress: true),
        );
        //fetch more announcements
        //more announcements result
        final moreAssignmentsResult =
            await _announcementRepository.fetchAnnouncements(
          subjectId: subjectId,
          isGeneralAnnouncement: false,
          childId: childId ?? 0,
          useParentApi: useParentApi,
          page: (state as SubjectAnnouncementFetchSuccess).currentPage + 1,
        );

        final currentState = state as SubjectAnnouncementFetchSuccess;
        final List<Announcement> announcements = currentState.announcements;

        //add more announcements into original announcements list
        announcements.addAll(moreAssignmentsResult['announcements']);

        emit(
          SubjectAnnouncementFetchSuccess(
            fetchMoreAnnouncementsInProgress: false,
            announcements: announcements,
            moreAnnouncementsFetchError: false,
            currentPage: moreAssignmentsResult['currentPage'],
            totalPage: moreAssignmentsResult['totalPage'],
          ),
        );
      } catch (e) {
        emit(
          (state as SubjectAnnouncementFetchSuccess).copyWith(
            newFetchMoreAnnouncementsInProgress: false,
            newMoreAnnouncementsFetchError: true,
          ),
        );
      }
    }
  }
}
