import 'package:eschool/ui/screens/exam/onlineExam/models/answerOption.dart';

class Question {

  Question({
    this.questionType,
    this.answerOptions,
    this.answersCount = 1,
    this.id,
    this.note,
    this.question,
    this.imageUrl,
    this.attempted = false,
    this.submittedAnswerId,
    this.marks,
  });
  final String? question;
  final int? id;
  final String? imageUrl;
  //How many options the student has to select. The correct answers themselves
  //are not sent to the app before the exam is submitted.
  final int answersCount;
  final String? note;
  final List<int>? submittedAnswerId;
  final int? questionType;
  final List<AnswerOption>? answerOptions;
  final bool attempted;
  final String? marks;

  static Question fromJson(Map questionJson) {
    final List<AnswerOption> options = (questionJson['options'] as List)
        .map((e) => AnswerOption.fromJson(Map.from(e)))
        .toList();

    options.shuffle();

    return Question(
      id: questionJson['id'],
      imageUrl: questionJson['image'],
      answersCount: _answersCountFromJson(questionJson),
      question: questionJson['question'],
      note: questionJson['note'] ?? '',
      questionType: questionJson['question_type'] ?? 0,
      marks: questionJson['marks'].toString(),
      answerOptions: options,
      submittedAnswerId: [0],
    );
  }

  //Uses answers_count, or the number of (placeholder) answers for servers that
  //do not send it yet
  static int _answersCountFromJson(Map questionJson) {
    final dynamic answersCount = questionJson['answers_count'];
    final int? count = answersCount is num
        ? answersCount.toInt()
        : int.tryParse(answersCount?.toString() ?? '');
    final int fallbackCount = questionJson['answers'] is List
        ? (questionJson['answers'] as List).length
        : 0;
    final int result = count ?? fallbackCount;
    return result < 1 ? 1 : result;
  }

  Question updateQuestionWithAnswer({required List<int> submittedAnswerId}) {
    return Question(
      marks: marks,
      submittedAnswerId: submittedAnswerId,
      answerOptions: answerOptions,
      attempted: submittedAnswerId.isNotEmpty,
      answersCount: answersCount,
      id: id,
      imageUrl: imageUrl,
      note: note,
      question: question,
      questionType: questionType,
    );
  }
}
