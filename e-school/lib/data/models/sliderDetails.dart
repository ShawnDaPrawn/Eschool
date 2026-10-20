class SliderDetails {

  SliderDetails({required this.id, required this.imageUrl});

  factory SliderDetails.fromJson(Map<String, dynamic> json) {
    return SliderDetails(
      id: json['id'] ?? 0,
      imageUrl: json['image'] ?? '',
    );
  }
  final String imageUrl;
  final int id;
}
