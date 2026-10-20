import 'package:eschool/data/models/galleryAlbum.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class GalleryAlbumState {}

class GalleryAlbumInitial extends GalleryAlbumState {}

class GalleryAlbumFetchInProgress extends GalleryAlbumState {}

class GalleryAlbumFetchSuccess extends GalleryAlbumState {
  final List<GalleryAlbum> albums;
  final int currentPage;
  final int totalPages;
  final bool moreAlbumsFetchError;
  final bool moreAlbumsFetchInProgress;

  GalleryAlbumFetchSuccess({
    required this.albums,
    required this.currentPage,
    required this.totalPages,
    required this.moreAlbumsFetchError,
    required this.moreAlbumsFetchInProgress,
  });

  GalleryAlbumFetchSuccess copyWith({
    List<GalleryAlbum>? newAlbums,
    int? newCurrentPage,
    int? newTotalPages,
    bool? newMoreAlbumsFetchInProgress,
    bool? newMoreAlbumsFetchError,
  }) {
    return GalleryAlbumFetchSuccess(
      albums: newAlbums ?? albums,
      currentPage: newCurrentPage ?? currentPage,
      totalPages: newTotalPages ?? totalPages,
      moreAlbumsFetchInProgress:
          newMoreAlbumsFetchInProgress ?? moreAlbumsFetchInProgress,
      moreAlbumsFetchError: newMoreAlbumsFetchError ?? moreAlbumsFetchError,
    );
  }
}

class GalleryAlbumFetchFailure extends GalleryAlbumState {
  final String errorMessage;

  GalleryAlbumFetchFailure({required this.errorMessage});
}

class GalleryAlbumCubit extends Cubit<GalleryAlbumState> {
  GalleryAlbumCubit() : super(GalleryAlbumInitial());

  final _systemRepository = SystemRepository();

  bool isLoading() {
    if (state is GalleryAlbumFetchInProgress) {
      return true;
    }
    return false;
  }

  Future<void> fetchAlbums() async {
    emit(GalleryAlbumFetchInProgress());
    try {
      var value = await _systemRepository.fetchGalleryAlbums(page: 1);

      emit(
        GalleryAlbumFetchSuccess(
          albums: value.albums,
          totalPages: value.totalPages,
          currentPage: value.currentPage,
          moreAlbumsFetchError: false,
          moreAlbumsFetchInProgress: false,
        ),
      );
    } catch (e) {
      emit(GalleryAlbumFetchFailure(errorMessage: e.toString()));
    }
  }

  Future<void> fetchMoreAlbums() async {
    if (state is GalleryAlbumFetchSuccess) {
      final stateAs = state as GalleryAlbumFetchSuccess;
      if (stateAs.moreAlbumsFetchInProgress) {
        return;
      }
      try {
        emit(stateAs.copyWith(newMoreAlbumsFetchInProgress: true));

        var moreResult = await _systemRepository.fetchGalleryAlbums(
          page: stateAs.currentPage + 1,
        );

        final List<GalleryAlbum> albums = stateAs.albums;

        albums.addAll(moreResult.albums);

        emit(
          GalleryAlbumFetchSuccess(
            albums: albums,
            totalPages: moreResult.totalPages,
            currentPage: moreResult.currentPage,
            moreAlbumsFetchError: false,
            moreAlbumsFetchInProgress: false,
          ),
        );
      } catch (e) {
        emit(
          (state as GalleryAlbumFetchSuccess).copyWith(
            newMoreAlbumsFetchInProgress: false,
            newMoreAlbumsFetchError: true,
          ),
        );
      }
    }
  }

  bool hasMore() {
    if (state is GalleryAlbumFetchSuccess) {
      return (state as GalleryAlbumFetchSuccess).currentPage <
          (state as GalleryAlbumFetchSuccess).totalPages;
    }
    return false;
  }
}
