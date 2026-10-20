class ResultOnlineDetails {
  ResultOnlineDetails({
    this.totalQuestions,
    this.correctAnswers,
    this.inCorrectAnswers,
    this.totalObtainedMarks,
    this.totalMarks,
  });

  ResultOnlineDetails.fromJson(Map<String, dynamic> json) {
    totalQuestions = json['total_questions'];
    correctAnswers = json['correct_answers'] != null
        ? ResultAnswers.fromJson(json['correct_answers'])
        : null;
    inCorrectAnswers = json['in_correct_answers'] != null
        ? ResultAnswers.fromJson(json['in_correct_answers'])
        : null;
    totalObtainedMarks = json['total_obtained_marks'];
    totalMarks = json['total_marks'];
  }

  late final int? totalQuestions;
  late final ResultAnswers? correctAnswers;
  late final ResultAnswers? inCorrectAnswers;
  late final String? totalObtainedMarks;
  late final String? totalMarks;
}

class ResultAnswers {

  ResultAnswers({this.totalQuestions, this.questionData});

  ResultAnswers.fromJson(Map<String, dynamic> json) {
    totalQuestions = json['total_questions'];
    if (json['question_data'] != null) {
      questionData = <QuestionData>[];
      json['question_data'].forEach((v) {
        questionData!.add(QuestionData.fromJson(v));
      });
    }
  }
  int? totalQuestions;
  List<QuestionData>? questionData;
}

class QuestionData {

  QuestionData({this.questionId, this.marks});

  QuestionData.fromJson(Map<String, dynamic> json) {
    questionId = json['question_id'];
    marks = json['marks'];
  }
  int? questionId;
  int? marks;

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['question_id'] = questionId;
    data['marks'] = marks;
    return data;
  }
}
