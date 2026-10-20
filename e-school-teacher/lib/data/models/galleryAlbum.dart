class GalleryAlbum {
  final String id;
  final String title;
  final String thumbnail;

  GalleryAlbum({
    required this.id,
    required this.title,
    required this.thumbnail,
  });

  factory GalleryAlbum.fromJson(Map<String, dynamic> json) {
    return GalleryAlbum(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      thumbnail: json['thumbnail_url']?.toString() ?? '',
    );
  }
}
