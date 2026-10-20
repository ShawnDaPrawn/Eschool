import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class GalleryVideosState {}

class GalleryVideosInitial extends GalleryVideosState {}

class GalleryVideosFetchInProgress extends GalleryVideosState {}

class GalleryVideosFetchSuccess extends GalleryVideosState {
  final List<String> videos;
  final int currentPage;
  final int totalPages;
  final bool moreVideosFetchError;
  final bool moreVideosFetchInProgress;

  GalleryVideosFetchSuccess({
    required this.videos,
    required this.currentPage,
    required this.totalPages,
    required this.moreVideosFetchError,
    required this.moreVideosFetchInProgress,
  });

  GalleryVideosFetchSuccess copyWith({
    List<String>? newVideos,
    int? newCurrentPage,
    int? newTotalPages,
    bool? newMoreVideosFetchInProgress,
    bool? newMoreVideosFetchError,
  }) {
    return GalleryVideosFetchSuccess(
      videos: newVideos ?? videos,
      currentPage: newCurrentPage ?? currentPage,
      totalPages: newTotalPages ?? totalPages,
      moreVideosFetchInProgress:
          newMoreVideosFetchInProgress ?? moreVideosFetchInProgress,
      moreVideosFetchError: newMoreVideosFetchError ?? moreVideosFetchError,
    );
  }
}

class GalleryVideosFetchFailure extends GalleryVideosState {
  final String errorMessage;

  GalleryVideosFetchFailure({required this.errorMessage});
}

class GalleryVideosCubit extends Cubit<GalleryVideosState> {
  GalleryVideosCubit() : super(GalleryVideosInitial());

  final _systemRepository = SystemRepository();

  bool isLoading() {
    if (state is GalleryVideosFetchInProgress) {
      return true;
    }
    return false;
  }

  Future<void> fetchVideos() async {
    emit(GalleryVideosFetchInProgress());
    try {
      var value = await _systemRepository.fetchGalleryVideos(page: 1);

      emit(
        GalleryVideosFetchSuccess(
          videos: value.videos,
          totalPages: value.totalPages,
          currentPage: value.currentPage,
          moreVideosFetchError: false,
          moreVideosFetchInProgress: false,
        ),
      );
    } catch (e) {
      emit(GalleryVideosFetchFailure(errorMessage: e.toString()));
    }
  }

  Future<void> fetchMoreVideos() async {
    if (state is GalleryVideosFetchSuccess) {
      final stateAs = state as GalleryVideosFetchSuccess;
      if (stateAs.moreVideosFetchInProgress) {
        return;
      }
      try {
        emit(stateAs.copyWith(newMoreVideosFetchInProgress: true));

        var moreResult = await _systemRepository.fetchGalleryVideos(
          page: stateAs.currentPage + 1,
        );

        final List<String> videos = stateAs.videos;

        videos.addAll(moreResult.videos);

        emit(
          GalleryVideosFetchSuccess(
            videos: videos,
            totalPages: moreResult.totalPages,
            currentPage: moreResult.currentPage,
            moreVideosFetchError: false,
            moreVideosFetchInProgress: false,
          ),
        );
      } catch (e) {
        emit(
          (state as GalleryVideosFetchSuccess).copyWith(
            newMoreVideosFetchInProgress: false,
            newMoreVideosFetchError: true,
          ),
        );
      }
    }
  }

  bool hasMore() {
    if (state is GalleryVideosFetchSuccess) {
      return (state as GalleryVideosFetchSuccess).currentPage <
          (state as GalleryVideosFetchSuccess).totalPages;
    }
    return false;
  }
}
