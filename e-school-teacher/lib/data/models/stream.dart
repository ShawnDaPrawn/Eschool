class Stream {
  Stream({
    required this.id,
    required this.name,
  });

  Stream.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    name = json['name'];
  }
  late final int id;
  late final String name;

  Map<String, dynamic> toJson() {
    final data = <String, dynamic>{};
    data['id'] = id;
    data['name'] = name;
    return data;
  }
}
