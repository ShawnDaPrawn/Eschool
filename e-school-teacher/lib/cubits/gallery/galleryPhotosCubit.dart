import 'package:eschool_teacher/data/models/galleryAlbum.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class GalleryAlbumPhotosState {}

class GalleryAlbumPhotosInitial extends GalleryAlbumPhotosState {}

class GalleryAlbumPhotosFetchInProgress extends GalleryAlbumPhotosState {}

class GalleryAlbumPhotosFetchSuccess extends GalleryAlbumPhotosState {
  final List<String> photos;

  GalleryAlbumPhotosFetchSuccess({required this.photos});
}

class GalleryAlbumPhotosFetchFailure extends GalleryAlbumPhotosState {
  final String errorMessage;

  GalleryAlbumPhotosFetchFailure({required this.errorMessage});
}

class GalleryAlbumPhotosCubit extends Cubit<GalleryAlbumPhotosState> {
  final GalleryAlbum album;
  GalleryAlbumPhotosCubit({required this.album})
    : super(GalleryAlbumPhotosInitial());

  final _systemRepository = SystemRepository();

  Future<void> fetchAlbumPhotos() async {
    emit(GalleryAlbumPhotosFetchInProgress());
    try {
      var value = await _systemRepository.fetchGalleryAlbumPhotos(
        albumId: album.id,
      );

      emit(GalleryAlbumPhotosFetchSuccess(photos: value));
    } catch (e) {
      emit(GalleryAlbumPhotosFetchFailure(errorMessage: e.toString()));
    }
  }
}
