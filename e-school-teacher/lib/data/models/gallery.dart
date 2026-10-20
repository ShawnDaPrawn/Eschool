import 'package:eschool_teacher/data/models/galleryAlbum.dart';

class Gallery {
  final List<GalleryAlbum>? albums;
  final List<String>? videos;

  Gallery({this.albums, this.videos});

  factory Gallery.fromJson(Map<String, dynamic> json) {
    return Gallery(
      albums: (json['albums'] as List?)
          ?.map((e) => GalleryAlbum.fromJson(e))
          .toList(),
      videos: (json['videos'] as List?)?.map((e) => e.toString()).toList(),
    );
  }

  bool hasData() {
    return (albums != null && albums!.isNotEmpty) ||
        (videos != null && videos!.isNotEmpty);
  }
}
