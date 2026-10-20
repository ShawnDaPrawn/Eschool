class AnswerOption {

  AnswerOption({this.id, this.option});
  AnswerOption.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    option = json['option'];
  }
  late final int? id;
  late final String? option;
}
