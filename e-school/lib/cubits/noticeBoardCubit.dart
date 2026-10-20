import 'package:eschool/data/models/announcement.dart';
import 'package:eschool/data/repositories/announcementRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class NoticeBoardState {}

class NoticeBoardInitial extends NoticeBoardState {}

class NoticeBoardFetchInProgress extends NoticeBoardState {}

class NoticeBoardFetchSuccess extends NoticeBoardState {

  NoticeBoardFetchSuccess({
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

  NoticeBoardFetchSuccess copyWith({
    List<Announcement>? newAnnouncements,
    bool? newFetchMoreAnnouncementsInProgress,
    bool? newMoreAnnouncementsFetchError,
    int? newCurrentPage,
    int? newTotalPage,
  }) {
    return NoticeBoardFetchSuccess(
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

class NoticeBoardFetchFailure extends NoticeBoardState {

  NoticeBoardFetchFailure({required this.errorMessage});
  final String errorMessage;
}

class NoticeBoardCubit extends Cubit<NoticeBoardState> {

  NoticeBoardCubit(this._announcementRepository) : super(NoticeBoardInitial());
  final AnnouncementRepository _announcementRepository;

  Future<void> fetchNoticeBoardDetails({required bool useParentApi}) async {
    try {
      emit(NoticeBoardFetchInProgress());
      final result = await _announcementRepository.fetchAnnouncements(
        isGeneralAnnouncement: true,
        childId: 0,
        useParentApi: useParentApi,
      );
      emit(
        NoticeBoardFetchSuccess(
          announcements: result['announcements'],
          fetchMoreAnnouncementsInProgress: false,
          moreAnnouncementsFetchError: false,
          currentPage: result['currentPage'],
          totalPage: result['totalPage'],
        ),
      );
    } catch (e) {
      emit(NoticeBoardFetchFailure(errorMessage: e.toString()));
    }
  }

  bool hasMore() {
    if (state is NoticeBoardFetchSuccess) {
      return (state as NoticeBoardFetchSuccess).currentPage <
          (state as NoticeBoardFetchSuccess).totalPage;
    }
    return false;
  }

  Future<void> fetchMoreAnnouncements({required bool useParentApi}) async {
    if (state is NoticeBoardFetchSuccess) {
      if ((state as NoticeBoardFetchSuccess).fetchMoreAnnouncementsInProgress) {
        return;
      }
      try {
        emit(
          (state as NoticeBoardFetchSuccess)
              .copyWith(newFetchMoreAnnouncementsInProgress: true),
        );
        //fetch more announcements
        //more announcements result
        final moreAssignmentsResult =
            await _announcementRepository.fetchAnnouncements(
          isGeneralAnnouncement: true,
          childId: 0,
          useParentApi: useParentApi,
          page: (state as NoticeBoardFetchSuccess).currentPage + 1,
        );

        final currentState = state as NoticeBoardFetchSuccess;
        final List<Announcement> announcements = currentState.announcements;

        //add more announcements into original announcements list
        announcements.addAll(moreAssignmentsResult['announcements']);

        emit(
          NoticeBoardFetchSuccess(
            fetchMoreAnnouncementsInProgress: false,
            announcements: announcements,
            moreAnnouncementsFetchError: false,
            currentPage: moreAssignmentsResult['currentPage'],
            totalPage: moreAssignmentsResult['totalPage'],
          ),
        );
      } catch (e) {
        emit(
          (state as NoticeBoardFetchSuccess).copyWith(
            newFetchMoreAnnouncementsInProgress: false,
            newMoreAnnouncementsFetchError: true,
          ),
        );
      }
    }
  }
}
